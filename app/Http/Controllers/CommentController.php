<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class CommentController extends Controller
{
  public function store(Request $request, $database)
  {
    $request->validate([
      'body' => 'required',
      'commentable_id' => 'required|integer',
      'commentable_type' => 'required|string|in:App\Models\Post,App\Models\Article',
    ]);

    try {
      DB::connection($database)->beginTransaction();

      // جلب المحتوى (الخبر أو المقال) من قاعدة البيانات الصحيحة
      $contentModel = app($request->commentable_type)::on($database)
        ->find($request->commentable_id);

      if (!$contentModel) {
        return redirect()->back()->with('error', 'المحتوى غير موجود');
      }

      // إنشاء التعليق مع تحديد العلاقات
      $comment = new Comment();
      $comment->setConnection($database);
      $comment->fill([
        'body' => $request->body,
        'user_id' => Auth::id(),
        'commentable_id' => $request->commentable_id,
        'commentable_type' => $request->commentable_type,
        'database' => $database,
      ]);

      Log::info('Saving comment', [
        'database' => $database,
        'comment_data' => $comment->toArray()
      ]);

      $comment->save();

      if ($request->commentable_type === Article::class) {
        $articleCacheKey = sprintf('article_full_%s_%d', $database, $contentModel->id);
        $articleRenderedKey = sprintf(
          'article_rendered_%s_%d_%d',
          $database,
          $contentModel->id,
          $contentModel->updated_at?->getTimestamp() ?? 0
        );

        Cache::forget($articleCacheKey);
        Cache::forget($articleRenderedKey);
      }

      // بناء رابط مباشر للتعليق باستخدام المسارات الاسمية للمقال/الخبر
      try {
        $commentUrlBase = null;
        if ($request->commentable_type === \App\Models\Article::class) {
          $commentUrlBase = route('frontend.articles.show', [
            'database' => $database,
            'article' => $request->commentable_id,
          ]);
        } elseif ($request->commentable_type === \App\Models\Post::class) {
          $commentUrlBase = route('content.frontend.posts.show', [
            'database' => $database,
            'id' => $request->commentable_id,
          ]);
        }
        if (!$commentUrlBase) {
          $commentUrlBase = $request->headers->get('referer') ?: url()->previous() ?: url()->current();
        }
        $commentUrl = rtrim($commentUrlBase, '#') . '#comment-' . $comment->id;
      } catch (\Throwable $e) {
        // fallback للمرجع إذا فشل بناء الرابط من المسارات
        $referer = $request->headers->get('referer') ?: url()->previous() ?: url()->current();
        $commentUrl = rtrim($referer, '#') . '#comment-' . $comment->id;
      }

      // تسجيل النشاط مع رابط التعليق لعرضه في الـ Timeline
      try {
        activity()
          ->performedOn($contentModel)
          ->causedBy(Auth::user())
          ->withProperties([
            'type' => 'comment',
            'url' => $commentUrl,
            'comment_id' => $comment->id,
            'database' => $database,
            'commentable_type' => $request->commentable_type,
            'commentable_id' => $request->commentable_id,
          ])
          ->log('commented');
      } catch (\Throwable $e) {
        Log::warning('Failed to log activity for comment', ['error' => $e->getMessage()]);
      }

      DB::connection($database)->commit();

      return redirect()->to($commentUrl)->with('success', 'تم إضافة التعليق بنجاح!');
    } catch (\Exception $e) {
      DB::connection($database)->rollBack();
      Log::error('Error saving comment: ' . $e->getMessage(), [
        'database' => $database,
        'error' => $e,
        'request_data' => $request->all()
      ]);
      return redirect()->back()->with('error', 'حدث خطأ أثناء حفظ التعليق');
    }
  }

  public function destroy(Request $request, $database, $id)
  {
    try {
      DB::connection($database)->beginTransaction();

      Log::info('Attempting to delete comment', [
        'comment_id' => $id,
        'database' => $database,
        'user_id' => Auth::id()
      ]);

      // تمكين تسجيل الاستعلامات للتصحيح
      DB::connection($database)->enableQueryLog();

      // البحث عن التعليق في قاعدة البيانات الصحيحة
      $comment = Comment::on($database)
        ->where('database', $database)
        ->findOrFail($id);

      Log::info('Found comment', [
        'comment' => $comment->toArray()
      ]);

      $user = Auth::user();
      $canDelete = $user && (
        $user->id === $comment->user_id ||
        (method_exists($user, 'hasRole') && $user->hasRole('Admin'))
      );

      if (!$canDelete) {
        return redirect()->back()->with('error', 'غير مصرح لك بحذف هذا التعليق');
      }

      // حذف ردود الأفعال المرتبطة بالتعليق أولاً
      if ($comment->reactions()->count() > 0) {
        Log::info('Deleting reactions', [
          'reactions_count' => $comment->reactions()->count()
        ]);
        $comment->reactions()->delete();
      }

      // حذف التعليق
      $deleted = $comment->delete();

      if ($deleted && $comment->commentable_type === Article::class) {
        $articleCacheKey = sprintf('article_full_%s_%d', $database, $comment->commentable_id);

        /** @var \App\Models\Article|null $article */
        $article = Article::on($database)->find($comment->commentable_id);

        $articleRenderedKey = $article
          ? sprintf(
            'article_rendered_%s_%d_%d',
            $database,
            $article->id,
            $article->updated_at?->getTimestamp() ?? 0
          )
          : null;

        Cache::forget($articleCacheKey);

        if ($articleRenderedKey) {
          Cache::forget($articleRenderedKey);
        }
      }

      DB::connection($database)->commit();

      Log::info('Comment deletion result', [
        'deleted' => $deleted,
        'queries' => DB::connection($database)->getQueryLog()
      ]);

      if (!$deleted) {
        throw new \Exception('Failed to delete comment');
      }

      return redirect()->back()->with('success', 'تم حذف التعليق بنجاح');
    } catch (\Exception $e) {
      DB::connection($database)->rollBack();

      Log::error('Comment deletion error: ' . $e->getMessage(), [
        'comment_id' => $id,
        'database' => $database,
        'exception' => $e,
        'queries' => DB::connection($database)->getQueryLog() ?? [],
        'request_data' => $request->all()
      ]);
      return redirect()->back()->with('error', 'فشل في حذف التعليق');
    }
  }
}

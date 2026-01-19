@php
use Illuminate\Support\Str;
@endphp

@extends('layouts/contentNavbarLayout')

@section('title', __('Recent Activities'))

@section('content')
<div class="container-fluid p-0">
    <div class="card">
        <div class="card-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <h5 class="card-title mb-0">{{ __('Recent Activities') }}</h5>
            <form class="w-100 w-md-auto" method="GET" action="{{ route('dashboard.activities.index') }}">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-3">
                        <label class="form-label mb-1">{{ __('Search') }}</label>
                        <input type="text" class="form-control" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('Search description or data...') }}">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label mb-1">{{ __('Type') }}</label>
                        <select class="form-select" name="type">
                            @php $type = $filters['type'] ?? ''; @endphp
                            <option value="">{{ __('All') }}</option>
                            <option value="article" @selected($type==='article')>Article</option>
                            <option value="news" @selected($type==='news')>News</option>
                            <option value="comment" @selected($type==='comment')>Comment</option>
                            <option value="user" @selected($type==='user')>User</option>
                            <option value="system" @selected($type==='system')>System</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label mb-1">{{ __('From') }}</label>
                        <input type="date" class="form-control" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label mb-1">{{ __('To') }}</label>
                        <input type="date" class="form-control" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
                    </div>
                    <div class="col-12 col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100">{{ __('Filter') }}</button>
                        <a href="{{ route('dashboard.activities.index') }}" class="btn btn-outline-secondary" title="{{ __('Reset') }}">
                            <i class="bx bx-reset"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div id="activities-container">
                @include('content.dashboard.activities._list')
            </div>

            <div id="activities-loader" class="text-center d-none">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>
    </div>
    <div class="text-center text-muted small mt-2">{{ __('Auto-loading more as you scroll') }}</div>
</div>
@endsection

@section('page-script')
<style>
  /* Highlight target comment if navigated via deep link */
  .comment-highlight {
    animation: flash-highlight 2s ease-in-out;
    box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.35);
    border-radius: 0.5rem;
  }
  @keyframes flash-highlight {
    0% { background-color: rgba(25, 135, 84, 0.08); }
    50% { background-color: rgba(25, 135, 84, 0.18); }
    100% { background-color: transparent; }
  }
</style>
<script>
let page = 1;
let loading = false;
let hasMore = true;
const filters = @json($filters ?? []);

// Auto-scroll and highlight a comment by hash (e.g., #comment-123)
function scrollToCommentFromHash() {
  const { hash } = window.location;
  if (!hash || !hash.startsWith('#comment-')) return;
  const el = document.querySelector(hash);
  if (!el) return;
  el.scrollIntoView({ behavior: 'smooth', block: 'center' });
  el.classList.add('comment-highlight');
  setTimeout(() => el.classList.remove('comment-highlight'), 2500);
}

function toQuery(params) {
  const usp = new URLSearchParams();
  Object.entries(params).forEach(([k, v]) => {
    if (v !== undefined && v !== null && String(v).trim() !== '') usp.append(k, v);
  });
  return usp.toString();
}

const loadMoreActivities = () => {
    if (loading || !hasMore) return;

    loading = true;
    page++;

    const loader = document.getElementById('activities-loader');
    loader.classList.remove('d-none');

    const qs = toQuery({ ...filters, page });
    fetch(`/dashboard/activities/load-more?${qs}`)
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('activities-container');
            container.insertAdjacentHTML('beforeend', data.html);
            hasMore = data.hasMore;
            loading = false;
            loader.classList.add('d-none');
            // Re-attempt highlighting if the target comment just loaded
            scrollToCommentFromHash();
        })
        .catch(error => {
            console.error('Error loading more activities:', error);
            loading = false;
            loader.classList.add('d-none');
        });
};

// Detect when user scrolls near bottom
window.addEventListener('scroll', () => {
    if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 1000) {
        loadMoreActivities();
    }
});

// Initial deep-link handling on first load
document.addEventListener('DOMContentLoaded', scrollToCommentFromHash);
</script>
@endsection

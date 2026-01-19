import jQuery from 'jquery';
// Guarantee $ exists even if this module loads before app.js
window.$ = window.$ || jQuery;
window.jQuery = window.jQuery || jQuery;
const $ = window.$;
import 'summernote/dist/summernote-bs5.min.css';
import 'summernote/dist/summernote-bs5.min.js';
import 'summernote/dist/lang/summernote-ar-AR.js';
import axios from 'axios';
import Swal from 'sweetalert2';

$(document).ready(function () {
  $('#summernote').summernote({
    codeviewFilter: false,
    codeviewIframeFilter: true,
    disableDragAndDrop: true,
    height: 400,
    lang: 'ar-AR',
    dialogsInBody: true,
    toolbar: [
      ['style', ['style']],
      ['font', ['bold', 'italic', 'underline', 'clear']],
      ['fontsize', ['fontsize']],
      ['color', ['color']],
      ['para', ['ul', 'ol', 'paragraph']],
      ['table', ['table']],
      ['insert', ['link', 'picture' ]],
      ['view', ['fullscreen', 'codeview', 'help']]
    ],
    buttons: {
      attachFile: function(context) {
        var ui = $.summernote.ui;
        var button = ui.button({
          contents: '<i class="fas fa-paperclip" style="color: #007bff;"></i>',
          tooltip: 'إرفاق ملف',
          className: 'btn-attach-file',
          click: function () {
            showFileAttachmentDialog(context);
          }
        });
        return button.render();
      }
    },
    callbacks: {
      onImageUpload: function (files) {
        handleImageUpload(files[0]);
      },
      onPaste: function (e) {
        handlePasteEvent(e);
      },
      onChange: function(contents, $editable) {
        // يمكن إضافة معالجة إضافية هنا عند تغيير المحتوى
        updateAttachedFilesDisplay();
      }
    },
    popover: {
      image: [
        ['imagesize', ['imageSize100', 'imageSize50', 'imageSize25']],
        ['float', ['floatLeft', 'floatRight', 'floatNone']],
        ['remove', ['removeMedia']]
      ],
      link: [
        ['link', ['linkDialogShow', 'unlink']],
        ['custom', ['downloadFile']]
      ]
    }
  });

  // إضافة أنماط CSS مخصصة
  addCustomStyles();
});

// إضافة أنماط CSS مخصصة للملفات المرفقة
function addCustomStyles() {
  const styles = `
    <style>
      /* RTL Support for Summernote */
      .note-editor, .note-editor .note-editing-area, .note-editor .note-editable {
        direction: rtl !important;
        text-align: right !important;
        font-family: 'Cairo', 'Tajawal', 'Almarai', sans-serif;
      }
      .note-toolbar {
        direction: rtl !important;
      }
      .note-btn-group {
        direction: ltr; /* Keep buttons LTR inside groups for correct borders/icons */
      }
      .dropdown-menu {
        text-align: right;
      }
      
      .file-attachment {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #155f65 0%, #22385a 100%)
        color: white !important;
        padding: 8px 12px;
        border-radius: 20px;
        text-decoration: none !important;
        margin: 2px;
        font-size: 13px;
        font-weight: 500;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        transition: all 0.3s ease;
        border: none;
      }

      .file-attachment:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        color: white !important;
        text-decoration: none !important;
      }

      .file-attachment .file-icon {
        margin-left: 6px;
        font-size: 14px;
      }

      .file-attachment .file-info {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
      }

      .file-attachment .file-name {
        font-weight: 600;
        font-size: 12px;
      }

      .file-attachment .file-size {
        font-size: 10px;
        opacity: 0.8;
      }

      .file-attachment.pdf { background: linear-gradient(135deg, #ff6b6b, #ee5a24); }
      .file-attachment.doc, .file-attachment.docx { background: linear-gradient(135deg, #4dabf7, #339af0); }
      .file-attachment.xls, .file-attachment.xlsx { background: linear-gradient(135deg, #51cf66, #40c057); }
      .file-attachment.ppt, .file-attachment.pptx { background: linear-gradient(135deg, #ff8787, #ff6b6b); }
      .file-attachment.txt { background: linear-gradient(135deg, #868e96, #495057); }
      .file-attachment.zip, .file-attachment.rar, .file-attachment.7z { background: linear-gradient(135deg, #ffd43b, #fab005); }

      .btn-attach-file {
        position: relative;
      }

      .attachment-progress {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        background: white;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        min-width: 300px;
      }

      .progress-bar-custom {
        height: 6px;
        background: #e9ecef;
        border-radius: 3px;
        overflow: hidden;
      }

      .progress-fill {
        height: 100%;
         background: linear-gradient(135deg, #155f65 0%, #22385a 100%)
        transition: width 0.3s ease;
      }
    </style>
  `;
  $('head').append(styles);
}

// عرض نافذة إرفاق الملف
function showFileAttachmentDialog(context) {
  Swal.fire({
    title: 'إرفاق ملف',
    html: `
      <div class="mb-3">
        <label class="form-label fw-bold">اختر الملف</label>
        <input type="file" id="fileInput" class="form-control"
               accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip,.rar,.7z,.csv"
               onchange="handleFileSelection(this)">
        <div class="form-text">الحد الأقصى: 10 ميجابايت</div>
      </div>
      <div class="mb-3" id="displayOptions" style="display: none;">
        <label class="form-label fw-bold">خيارات العرض</label>
        <div class="form-check">
          <input class="form-check-input" type="radio" name="displayType" id="displayText" value="text" checked>
          <label class="form-check-label" for="displayText">
            عرض كنص مخصص
          </label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="radio" name="displayType" id="displayButton" value="button">
          <label class="form-check-label" for="displayButton">
            عرض كزر تحميل احترافي
          </label>
        </div>
      </div>
      <div class="mb-3" id="customTextDiv" style="display: none;">
        <label class="form-label fw-bold">النص المخصص</label>
        <input type="text" id="customText" class="form-control" placeholder="أدخل النص الذي تريد عرضه">
      </div>
      <div id="filePreview" class="mt-3"></div>
    `,
    width: 500,
    showCancelButton: true,
    confirmButtonText: 'إرفاق الملف',
    cancelButtonText: 'إلغاء',
    preConfirm: () => {
      const fileInput = document.getElementById('fileInput');
      const displayType = document.querySelector('input[name="displayType"]:checked').value;
      const customText = document.getElementById('customText').value;

      if (!fileInput.files[0]) {
        Swal.showValidationMessage('الرجاء اختيار ملف');
        return false;
      }

      if (displayType === 'text' && !customText.trim()) {
        Swal.showValidationMessage('الرجاء إدخال النص المخصص');
        return false;
      }

      return {
        file: fileInput.files[0],
        displayType: displayType,
        customText: customText.trim()
      };
    }
  }).then(result => {
    if (result.isConfirmed) {
      uploadFileWithProgress(result.value.file, context, result.value.displayType, result.value.customText);
    }
  });
}

// معالجة اختيار الملف
window.handleFileSelection = function(input) {
  const file = input.files[0];
  if (!file) return;

  // التحقق من حجم الملف
  const maxSize = 10 * 1024 * 1024; // 10MB
  if (file.size > maxSize) {
    Swal.showValidationMessage('حجم الملف يجب ألا يتجاوز 10 ميجابايت');
    input.value = '';
    return;
  }

  // إظهار خيارات العرض
  document.getElementById('displayOptions').style.display = 'block';
  document.getElementById('customTextDiv').style.display = 'block';

  // ملء النص المخصص بناءً على اسم الملف
  document.getElementById('customText').value = file.name.split('.')[0];

  // عرض معاينة الملف
  showFilePreview(file);

  // إضافة مستمع لتغيير نوع العرض
  document.querySelectorAll('input[name="displayType"]').forEach(radio => {
    radio.addEventListener('change', function() {
      const customTextDiv = document.getElementById('customTextDiv');
      customTextDiv.style.display = this.value === 'text' ? 'block' : 'none';
    });
  });
};

// عرض معاينة الملف
function showFilePreview(file) {
  const preview = document.getElementById('filePreview');
  const fileSize = formatFileSize(file.size);
  const fileIcon = getFileIcon(file.name);

  preview.innerHTML = `
    <div class="alert alert-info">
      <div class="d-flex align-items-center">
        <i class="${fileIcon} fa-2x me-3"></i>
        <div>
          <div class="fw-bold">${file.name}</div>
          <div class="small text-muted">الحجم: ${fileSize}</div>
        </div>
      </div>
    </div>
  `;
}

// رفع الملف مع شريط التقدم
function uploadFileWithProgress(file, context, displayType, customText) {
  const progressContainer = createProgressIndicator(file.name);
  document.body.appendChild(progressContainer);

  const formData = new FormData();
  formData.append("file", file);

  const controller = new AbortController();
  const timeoutId = setTimeout(() => {
    controller.abort();
    showUploadError('انتهت مهلة الرفع. يرجى المحاولة مرة أخرى.');
    progressContainer.remove();
  }, 60000);

  axios.post('/upload/file', formData, {
    headers: {
      'Content-Type': 'multipart/form-data',
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    },
    withCredentials: true,
    signal: controller.signal,
    onUploadProgress: (progressEvent) => {
      const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
      updateProgress(progressContainer, percentCompleted);
    }
  })
  .then(response => {
    clearTimeout(timeoutId);
    progressContainer.remove();

    if (response.status === 200 && response.data.url) {
      insertFileAttachment(context, file, response.data.url, displayType, customText);
      showSuccessMessage('تم رفع الملف بنجاح!');
    } else {
      showUploadError('فشل في رفع الملف. يرجى المحاولة مرة أخرى.');
    }
    // إضافة الملف للمحرر عند نجاح الرفع
    if (response.data.success) {
      insertFileAttachment(context, file, response.data.url, displayType, customText);
      showSuccessMessage('تم رفع الملف بنجاح!');
    }
  })
  .catch(error => {
    clearTimeout(timeoutId);
    progressContainer.remove();
    console.error('File upload failed:', error);

    let errorMessage = 'حدث خطأ أثناء رفع الملف.';
    if (error.name === 'AbortError') {
      errorMessage = 'انتهت مهلة رفع الملف.';
    } else if (error.response) {
      errorMessage = error.response.data.message || errorMessage;
    }

    showUploadError(errorMessage);
  });
}

// إنشاء مؤشر التقدم
function createProgressIndicator(fileName) {
  const container = document.createElement('div');
  container.className = 'attachment-progress';
  container.innerHTML = `
    <div class="d-flex align-items-center mb-2">
      <i class="fas fa-cloud-upload-alt me-2 text-primary"></i>
      <div class="flex-grow-1">
        <div class="fw-bold">جاري رفع الملف</div>
        <div class="small text-muted">${fileName}</div>
      </div>
    </div>
    <div class="progress-bar-custom">
      <div class="progress-fill" style="width: 0%"></div>
    </div>
    <div class="small text-center mt-1">0%</div>
  `;
  return container;
}

// تحديث شريط التقدم
function updateProgress(container, percent) {
  const progressFill = container.querySelector('.progress-fill');
  const progressText = container.querySelector('.small');

  progressFill.style.width = percent + '%';
  progressText.textContent = percent + '%';
}

// إدراج الملف المرفق في المحرر
function insertFileAttachment(context, file, fileUrl, displayType, customText) {
  const fileSize = formatFileSize(file.size);
  const fileExtension = getFileExtension(file.name);
  const fileIcon = getFileIcon(file.name);

  let htmlContent = `
  <p>
    <a href="${fileUrl}" target="_blank" rel="noopener noreferrer"
       class="file-attachment ${fileExtension}"
       title="تحميل ${file.name} (${fileSize})"
       style="margin:2px;display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:7px 14px;background:linear-gradient(135deg, #155f65 0%, #22385a 100%);color:white;border-radius:18px;text-decoration:none;">
      <i class="${fileIcon}" style="font-size:20px;"></i>
      <span>تحميل ${customText || file.name}</span>
    </a>
  </p>
`;

  context.invoke('editor.pasteHTML', htmlContent + '&nbsp;');
}


// معالجة رفع الصور
function handleImageUpload(file) {
  const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
  if (!allowedTypes.includes(file.type)) {
    showUploadError('يمكنك رفع ملفات الصور فقط (JPG, PNG, GIF, WebP)');
    return;
  }

  const maxSize = 5 * 1024 * 1024; // 5MB
  if (file.size > maxSize) {
    showUploadError('حجم الصورة يجب ألا يتجاوز 5 ميجابايت');
    return;
  }

  Swal.fire({
    title: 'إعدادات الصورة',
    html: `
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">العرض (بكسل)</label>
          <input type="number" id="imageWidth" class="form-control" min="50" max="1920" value="600">
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">الارتفاع (بكسل)</label>
          <input type="number" id="imageHeight" class="form-control" min="50" max="1080" value="auto">
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label">جودة الصورة</label>
        <input type="range" id="imageQuality" class="form-range" min="10" max="100" value="85">
        <div class="text-center fw-bold" id="qualityValue">85%</div>
      </div>
    `,
    showCancelButton: true,
    confirmButtonText: 'رفع الصورة',
    cancelButtonText: 'إلغاء',
    didOpen: () => {
      $('#imageQuality').on('input', function () {
        $('#qualityValue').text($(this).val() + '%');
      });
    }
  }).then(result => {
    if (result.isConfirmed) {
      const width = $('#imageWidth').val();
      const height = $('#imageHeight').val();
      const quality = $('#imageQuality').val();
      uploadImageWithProgress(file, width, height, quality);
    }
  });
}

// رفع الصورة مع شريط التقدم
function uploadImageWithProgress(file, width, height, quality) {
  const progressContainer = createProgressIndicator(file.name);
  document.body.appendChild(progressContainer);

  const formData = new FormData();
  formData.append("file", file);
  if (width && width !== 'auto') formData.append("width", width);
  if (height && height !== 'auto') formData.append("height", height);
  if (quality) formData.append("quality", quality);

  const controller = new AbortController();
  const timeoutId = setTimeout(() => {
    controller.abort();
    showUploadError('انتهت مهلة رفع الصورة');
    progressContainer.remove();
  }, 60000);

  axios.post('/upload/image', formData, {
    headers: {
      'Content-Type': 'multipart/form-data',
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    },
    withCredentials: true,
    signal: controller.signal,
    onUploadProgress: (progressEvent) => {
      const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
      updateProgress(progressContainer, percentCompleted);
    }
  })
  .then(response => {
    clearTimeout(timeoutId);
    progressContainer.remove();

    if (response.status === 200 && response.data.url) {
      $('#summernote').summernote('insertImage', response.data.url, function($image) {
        $image.attr('alt', file.name);
        $image.addClass('img-fluid');
        if (response.data.width && response.data.height) {
          $image.css({
            'max-width': '100%',
            'height': 'auto'
          });
        }
      });
      showSuccessMessage('تم رفع الصورة بنجاح!');
    } else {
      showUploadError('فشل في رفع الصورة');
    }
  })
  .catch(error => {
    clearTimeout(timeoutId);
    progressContainer.remove();
    console.error('Image upload failed:', error);
    showUploadError('حدث خطأ أثناء رفع الصورة');
  });
}

// معالجة أحداث اللصق
function handlePasteEvent(e) {
  const clipboardData = e.originalEvent.clipboardData;
  if (!clipboardData) return;

  const items = clipboardData.items;
  for (let i = 0; i < items.length; i++) {
    if (items[i].type.indexOf('image') !== -1) {
      e.preventDefault();
      const file = items[i].getAsFile();
      if (file) {
        handleImageUpload(file);
      }
      return;
    }
  }
}

// تحديث عرض الملفات المرفقة
function updateAttachedFilesDisplay() {
  // يمكن إضافة منطق لتتبع الملفات المرفقة
  const attachments = $('#summernote').summernote('code').match(/class="file-attachment/g);
  const count = attachments ? attachments.length : 0;

  // يمكن إضافة عداد الملفات في واجهة المستخدم
  if (count > 0) {
    console.log(`تم إرفاق ${count} ملف(ات)`);
  }
}

// الدوال المساعدة
function formatFileSize(bytes) {
  if (bytes === 0) return '0 بايت';
  const k = 1024;
  const sizes = ['بايت', 'كيلوبايت', 'ميجابايت', 'جيجابايت'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function getFileExtension(filename) {
  return filename.split('.').pop().toLowerCase();
}

function getFileIcon(filename) {
  const extension = getFileExtension(filename);
  const iconMap = {
    pdf: 'fas fa-file-pdf',
    doc: 'fas fa-file-word',
    docx: 'fas fa-file-word',
    xls: 'fas fa-file-excel',
    xlsx: 'fas fa-file-excel',
    ppt: 'fas fa-file-powerpoint',
    pptx: 'fas fa-file-powerpoint',
    txt: 'fas fa-file-alt',
    zip: 'fas fa-file-archive',
    rar: 'fas fa-file-archive',
    '7z': 'fas fa-file-archive',
    csv: 'fas fa-file-csv'
  };
  return iconMap[extension] || 'fas fa-file';
}

function showSuccessMessage(message) {
  const toast = $(`
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 9999;">
      <div class="toast show align-items-center text-white bg-success border-0" role="alert">
        <div class="d-flex">
          <div class="toast-body">
            <i class="fas fa-check-circle me-2"></i>${message}
          </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
      </div>
    </div>
  `);

  $('body').append(toast);
  setTimeout(() => toast.fadeOut(() => toast.remove()), 3000);
}

function showUploadError(message) {
  Swal.fire({
    icon: 'error',
    title: 'خطأ في الرفع',
    text: message,
    confirmButtonText: 'موافق'
  });
}

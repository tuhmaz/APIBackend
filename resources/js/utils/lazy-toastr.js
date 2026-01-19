// Lazy-load Toastr and its CSS on demand
let toastrPromise = null;

export async function getToastr() {
  if (!toastrPromise) {
    toastrPromise = Promise.all([
      import('toastr'),
      import('toastr/build/toastr.min.css')
    ]).then(([toastrModule]) => {
      const toastr = toastrModule.default || toastrModule;
      // Optional defaults
      toastr.options = Object.assign(
        {
          closeButton: true,
          progressBar: true,
          newestOnTop: true,
          timeOut: 3000,
          positionClass: 'toast-top-right'
        },
        toastr.options || {}
      );
      return toastr;
    });
  }
  return toastrPromise;
}

export async function toast(type, message, title = '') {
  const toastr = await getToastr();
  if (typeof toastr[type] === 'function') {
    toastr[type](message, title);
  } else {
    toastr.info(message, title);
  }
}

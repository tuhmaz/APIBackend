const e=`<div id="template-customizer" class="card rounded-0">\r
  <a href="javascript:void(0)" class="template-customizer-open-btn" tabindex="-1"></a>\r
\r
  <div class="p-6 m-0 lh-1 border-bottom template-customizer-header position-relative py-4">\r
    <h6 class="template-customizer-t-panel_header mb-1"></h6>\r
    <p class="template-customizer-t-panel_sub_header mb-0 small"></p>\r
    <div class="d-flex align-items-center gap-2 position-absolute end-0 top-0 mt-6 me-5">\r
      <a\r
        href="javascript:void(0)"\r
        class="template-customizer-reset-btn text-heading"\r
        data-bs-toggle="tooltip"\r
        data-bs-placement="bottom"\r
        title="Reset Customizer"\r
        ><i class="icon-base ti tabler-refresh icon-lg"></i\r
        ><span class="badge rounded-pill bg-danger badge-dot badge-notifications d-none"></span\r
      ></a>\r
      <a href="javascript:void(0)" class="template-customizer-close-btn fw-light text-heading" tabindex="-1">\r
        <i class="icon-base ti tabler-x icon-lg"></i>\r
      </a>\r
    </div>\r
  </div>\r
\r
  <div class="template-customizer-inner pt-6">\r
    <!-- Theming -->\r
    <div class="template-customizer-theming">\r
      <h5 class="m-0 px-6 pb-6">\r
        <span class="template-customizer-t-theming_header bg-label-primary rounded-1 py-1 px-3 small"></span>\r
      </h5>\r
\r
      <!-- Color -->\r
      <div class="m-0 px-6 pb-6 template-customizer-color w-100">\r
        <label for="customizerColor" class="form-label d-block template-customizer-t-color_label mb-2"></label>\r
        <div class="row template-customizer-colors-options"></div>\r
      </div>\r
\r
      <!-- Theme -->\r
      <div class="m-0 px-6 pb-6 template-customizer-theme w-100">\r
        <label for="customizerTheme" class="form-label d-block template-customizer-t-theme_label mb-2"></label>\r
        <div class="row px-1 template-customizer-themes-options"></div>\r
      </div>\r
\r
      <!-- Skins -->\r
      <div class="m-0 px-6 pb-6 template-customizer-skins w-100">\r
        <label for="customizerSkin" class="form-label template-customizer-t-skin_label mb-2"></label>\r
        <div class="row px-1 template-customizer-skins-options"></div>\r
      </div>\r
\r
      <!-- Semi Dark -->\r
      <div class="m-0 px-6 template-customizer-semiDark w-100 d-flex justify-content-between pe-12">\r
        <span class="form-label template-customizer-t-semiDark_label"></span>\r
        <label class="switch template-customizer-t-semiDark_label">\r
          <input type="checkbox" class="template-customizer-semi-dark-switch switch-input" />\r
          <span class="switch-toggle-slider">\r
            <span class="switch-on"></span>\r
            <span class="switch-off"></span>\r
          </span>\r
        </label>\r
      </div>\r
      <hr class="m-0 px-6 my-6" />\r
    </div>\r
    <!--/ Theming -->\r
\r
    <!-- Layout -->\r
    <div class="template-customizer-layout">\r
      <h5 class="m-0 px-6 pb-6">\r
        <span class="template-customizer-t-layout_header bg-label-primary rounded-2 py-1 px-3 small"></span>\r
      </h5>\r
\r
      <!-- Layout(Menu) -->\r
      <div class="m-0 px-6 pb-6 d-block template-customizer-layouts">\r
        <label for="customizerStyle" class="form-label d-block template-customizer-t-layout_label mb-2"></label>\r
        <div class="row px-1 template-customizer-layouts-options"></div>\r
      </div>\r
\r
      <!-- Header Options for Horizontal -->\r
      <div class="m-0 px-6 pb-6 template-customizer-headerOptions w-100">\r
        <label for="customizerHeader" class="form-label template-customizer-t-layout_header_label mb-2"></label>\r
        <div class="row px-1 template-customizer-header-options"></div>\r
      </div>\r
\r
      <!-- Fixed navbar -->\r
      <div class="m-0 px-6 pb-6 template-customizer-layoutNavbarOptions w-100">\r
        <label for="customizerNavbar" class="form-label template-customizer-t-layout_navbar_label mb-2"></label>\r
        <div class="row px-1 template-customizer-navbar-options"></div>\r
      </div>\r
\r
      <!-- Content -->\r
      <div class="m-0 px-6 pb-6 template-customizer-content w-100">\r
        <label for="customizerContent" class="form-label template-customizer-t-content_label mb-2"></label>\r
        <div class="row px-1 template-customizer-content-options"></div>\r
      </div>\r
\r
      <!-- Directions -->\r
      <div class="m-0 px-6 pb-6 template-customizer-directions w-100">\r
        <label for="customizerDirection" class="form-label template-customizer-t-direction_label mb-2"></label>\r
        <div class="row px-1 template-customizer-directions-options"></div>\r
      </div>\r
    </div>\r
    <!--/ Layout -->\r
  </div>\r
</div>\r
`;export{e as c};

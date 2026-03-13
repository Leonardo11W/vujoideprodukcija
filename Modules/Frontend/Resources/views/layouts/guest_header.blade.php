<header>
  
   <nav class="nav navbar navbar-expand-xl navbar-light iq-navbar header-hover-menu py-xl-0">
      <div class="container-fluid navbar-inner">
         <div class="d-flex align-items-center justify-content-between w-100 landing-header">
            <div class="d-flex gap-2 gap-sm-3 align-items-center">
               <button data-bs-toggle="offcanvas" data-bs-target="#navbar_main" aria-controls="navbar_main"
                  class="d-xl-none btn btn-primary rounded-pill toggle-rounded-btn" type="button">
                  <i class="ph ph-arrow-right"></i>
               </button>
               <!--Logo -->
               <x-logo />               
               <!-- menu -->
               <!-- menu end -->
            </div>
            <div class="right-panel">
               <ul class="navbar-nav align-items-center d-xl-none">
                   <!-- color mode -->
                   <li class="nav-item theme-scheme-switch">
                     <a href="javascript:void(0)" class="nav-link d-flex align-items-center change-mode">
                        <span class="light-mode">
                              <i class="ph ph-sun"></i>
                        </span>
                        <span class="dark-mode">
                              <i class="ph ph-moon"></i>
                        </span>
                     </a>
                  </li> 
                  <!-- user droupdown -->
                                          
               </ul>
               <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                  data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
                  aria-label="Toggle navigation">
                     <i class="ph ph-list toggle-list fs-4"></i>
                     <i class="ph ph-x toggle-x fs-4"></i>
               </button>
               <div class="collapse navbar-collapse" id="navbarSupportedContent">
                  <ul class="navbar-nav align-items-center ms-auto mb-2 mb-xl-0">
                     <!-- search -->
                   
                     <!-- color mode -->
                     <li class="nav-item theme-scheme-switch d-none d-xl-block">
                        <a href="javascript:void(0)" class="nav-link d-flex align-items-center change-mode">
                           <span class="light-mode">
                                 <i class="ph ph-sun"></i>
                           </span>
                           <span class="dark-mode">
                                 <i class="ph ph-moon"></i>
                           </span>
                        </a>
                     </li>
                     <!-- Language -->
                     <li class="nav-item dropdown dropdown-language-wrapper">
                        <button class="gap-3 px-3 dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                           <img src="https://apps.iqonic.design/streamit-laravel/flags/en.png" alt="flag" class="img-fluid mr-2 avatar-20" onerror="this.onerror=null; this.src='https://apps.iqonic.design/streamit-laravel/flags/globe.png';">
                           EN
                        </button>
                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-language mt-0">
                           <a class="dropdown-item" href="https://apps.iqonic.design/streamit-laravel/language/ar">
                              <span class="d-flex align-items-center gap-3">
                                 <img src="https://apps.iqonic.design/streamit-laravel/flags/ar.png" alt="flag" class="img-fluid mr-2 avatar-20">
                                 <span> العربی(AR)</span>
                                 <span class="active-icon"><i class="ph-fill ph-check-fat align-middle"></i></span>
                              </span>
                           </a>
                           <a class="dropdown-item" href="https://apps.iqonic.design/streamit-laravel/language/en">
                              <span class="d-flex align-items-center gap-3">
                                 <img src="https://apps.iqonic.design/streamit-laravel/flags/en.png" alt="flag" class="img-fluid mr-2 avatar-20">
                                 <span> English (EN)</span>
                                 <span class="active-icon"><i class="ph-fill ph-check-fat align-middle"></i></span>
                              </span>
                           </a>
                           <a class="dropdown-item" href="https://apps.iqonic.design/streamit-laravel/language/el">
                              <span class="d-flex align-items-center gap-3">
                                 <img src="https://apps.iqonic.design/streamit-laravel/flags/el.png" alt="flag" class="img-fluid mr-2 avatar-20">
                                 <span> Greek (EL)</span>
                                 <span class="active-icon"><i class="ph-fill ph-check-fat align-middle"></i></span>
                              </span>
                           </a>
                           <a class="dropdown-item" href="https://apps.iqonic.design/streamit-laravel/language/fr">
                              <span class="d-flex align-items-center gap-3">
                                 <img src="https://apps.iqonic.design/streamit-laravel/flags/fr.png" alt="flag" class="img-fluid mr-2 avatar-20">
                                 <span> French (FR)</span>
                                 <span class="active-icon"><i class="ph-fill ph-check-fat align-middle"></i></span>
                              </span>
                           </a>
                           <a class="dropdown-item" href="https://apps.iqonic.design/streamit-laravel/language/de">
                              <span class="d-flex align-items-center gap-3">
                                 <img src="https://apps.iqonic.design/streamit-laravel/flags/de.png" alt="flag" class="img-fluid mr-2 avatar-20">
                                 <span> German (DE)</span>
                                 <span class="active-icon"><i class="ph-fill ph-check-fat align-middle"></i></span>
                              </span>
                           </a>
                        </div>
                     </li>
                     <!-- notification -->
                  
                  </ul>
               </div>
            </div>
         </div>
      </div>
   </nav>
</header>

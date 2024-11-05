<nav class="navbar-custom-menu navbar navbar-expand-lg m-0">
    <div class="sidebar-toggle-icon" id="sidebarCollapse">
        sidebar toggle<span></span>
    </div>
  <div class="dashboard-logo">
    <img src="{{ asset('assets/dist/img/NeedsPaygreen.jpg') }}" alt="" height="50">
  </div><!--/.sidebar toggle icon-->
  <div class="d-flex flex-grow-1 menu">
    <ul class="navbar-nav flex-row align-items-center ml-auto">
        <li class="nav-item">
            <a class="nav-link menu @if(url()->current()==route('home')) active @endif d-flex align-center" href="/dashboard">
                <svg width="16" height="16" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M6.17675 6.32L3.97394 3.38L0.921627 5.83C0.368456 6.19 0.0424805 6.8 0.0424805 7.45V16.04C0.0424805 17.12 0.931505 18 2.00821 18H15.6794C16.766 18 17.6452 17.12 17.6452 16.04V7.45C17.6452 6.8 17.3192 6.19 16.766 5.83L13.7137 3.38L11.5109 6.32H6.17675ZM13.773 12L13.7038 3.38L9.92053 0.33C9.2587 -0.11 8.39931 -0.11 7.7276 0.33L3.94431 3.38L5.85077 12H13.773ZM8.61663 9.07C8.61663 9.07 8.65614 9.07 8.66602 9.07C8.65614 9.07 8.64626 9.07 8.63638 9.07H8.61663ZM8.96236 9.07C8.96236 9.07 9.00187 9.07 9.01175 9.07H8.99199C8.99199 9.07 8.97224 9.07 8.96236 9.07Z" fill="#008A4B"/>
                    <path d="M11.7974 14.97V18H5.91992V14.97C5.91992 13.62 6.89785 12.5 8.17212 12.3C8.30053 12.28 8.4487 12.27 8.58699 12.27H9.11053C9.24882 12.27 9.397 12.28 9.52541 12.3C10.7997 12.5 11.7776 13.63 11.7776 14.97H11.7974Z" fill="#D2FFD1"/>
                    <path d="M8.85378 10.5298C9.94488 10.5298 10.8294 9.63435 10.8294 8.52979C10.8294 7.42522 9.94488 6.52979 8.85378 6.52979C7.76268 6.52979 6.87817 7.42522 6.87817 8.52979C6.87817 9.63435 7.76268 10.5298 8.85378 10.5298Z" fill="#D2FFD1"/>
                </svg>
                <span class="ml-1">Home</span>
            </a>
        </li>

        <li class="nav-item dropdown">
            <a class="nav-link  @if(url()->current()==route('roles_home')) active @endif menu d-flex align-center" href="{{ route('roles_home') }}" >
                <svg width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9.55105 0.897891V0L6.54719 2.18238L9.55105 4.36473V3.46684C13.401 3.46684 16.5332 6.59899 16.5332 10.4489C16.5332 14.2989 13.401 17.4311 9.55105 17.4311C5.70109 17.4311 2.56895 14.2989 2.56895 10.4489H0C0 15.7238 4.27617 20 9.55105 20C14.8259 20 19.1021 15.7238 19.1021 10.4489C19.1021 5.17406 14.826 0.897891 9.55105 0.897891Z" fill="#008A4B"/>
                    <path d="M9.55102 14.7832C7.16109 14.7832 5.2168 12.8389 5.2168 10.449C5.2168 8.05909 7.16109 6.11475 9.55102 6.11475C11.9409 6.11475 13.8852 8.05909 13.8852 10.449C13.8852 12.8389 11.9409 14.7832 9.55102 14.7832Z" fill="#D2FFD1"/>
                    <path d="M9.55127 14.7832C9.55127 11.4684 9.55127 8.28295 9.55127 6.11475C11.9412 6.11475 13.8855 8.05909 13.8855 10.449C13.8855 12.8389 11.9412 14.7832 9.55127 14.7832Z" fill="#D2FFD1"/>
                    <path d="M9.55127 0.897891V0V4.36473V3.46684C13.4012 3.46684 16.5334 6.59898 16.5334 10.4489C16.5334 14.2989 13.4012 17.4311 9.55127 17.4311V20C14.8262 20 19.1023 15.7239 19.1023 10.4489C19.1023 5.17406 14.8262 0.897891 9.55127 0.897891Z" fill="#008A4B"/>
                    </svg>

                <span class="ml-1">Configurations</span>
            </a>
        </li>
    </ul>
  </div>
  <div class="d-flex flex-grow-1 more">
    <ul class="navbar-nav flex-row align-items-center ml-auto">
        <li class="nav-item">
            <a href="/dashboard/payment-request" class="btn btn-success">
                Manage Payment Requests
            </a>
        </li>
        <li class="nav-item dropdown notification">
            <a class="nav-link dropdown-toggle badge-dot" href="#" data-toggle="dropdown">
                <svg width="18" height="20" viewBox="0 0 18 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M6.96318 17.2279C7.46309 17.1222 10.5093 17.1222 11.0092 17.2279C11.4366 17.3266 11.8987 17.5573 11.8987 18.0608C11.8738 18.5402 11.5926 18.9653 11.204 19.2352C10.7001 19.628 10.1088 19.8767 9.49057 19.9664C9.14868 20.0107 8.81276 20.0117 8.48279 19.9664C7.86362 19.8767 7.27227 19.628 6.76938 19.2342C6.37978 18.9653 6.09852 18.5402 6.07367 18.0608C6.07367 17.5573 6.53582 17.3266 6.96318 17.2279ZM9.04522 0C11.1254 0 13.2502 0.987019 14.5125 2.62466C15.3314 3.67916 15.7071 4.73265 15.7071 6.3703V6.79633C15.7071 8.05226 16.039 8.79253 16.7695 9.64559C17.3231 10.2741 17.5 11.0808 17.5 11.956C17.5 12.8302 17.2128 13.6601 16.6373 14.3339C15.884 15.1417 14.8215 15.6573 13.7372 15.747C12.1659 15.8809 10.5937 15.9937 9.0005 15.9937C7.40634 15.9937 5.83505 15.9263 4.26375 15.747C3.17846 15.6573 2.11602 15.1417 1.36367 14.3339C0.78822 13.6601 0.5 12.8302 0.5 11.956C0.5 11.0808 0.677901 10.2741 1.23049 9.64559C1.98384 8.79253 2.29392 8.05226 2.29392 6.79633V6.3703C2.29392 4.68834 2.71333 3.58852 3.577 2.51186C4.86106 0.941697 6.91935 0 8.95577 0H9.04522Z" fill="#454545" fill-opacity="0.5"/>
                    </svg>
            </a>
            <div class="dropdown-menu dropdown-menu-right">
                <h6 class="notification-title">Notifications</h6>
                <p class="notification-text">You have 0 unread notification</p>
            <div class="notification-list">

                    <div class="media new">
                        <div class="img-user"><img src="assets/dist/img/avatar.png" alt=""></div>
                        <div class="media-body">
                            <h6></h6>
                            <span></span>
                        </div>
                    </div><!--/.media -->


                </div><!--/.notification -->
                <div class="dropdown-footer"><a href="#">View All Notifications</a></div>
            </div><!--/.dropdown-menu -->
        </li><!--/.dropdown-->
        <li class="nav-item dropdown user-menu">
            <a class="nav-link dropdown-toggle avatar" href="#" data-toggle="dropdown">
                @if(auth()->user()->logo)
                <img src="{{auth()->user()->logo}}" alt="">
                @else
                <img src="{{asset("assets/dist/img/header-avatar.png")}}" alt="">
                @endif

            </a>
            <div class="dropdown-menu dropdown-menu-right" >
                <div class="dropdown-header d-sm-none">
                    <a href="#" class="header-arrow"><i class="icon ion-md-arrow-back"></i></a>
                </div>
                <div class="user-header">
                    <h6 class="mb-2 text-center" style="white-space: nowrap">{{auth()->user()->first_name}} {{auth()->user()->last_name}}</h6>
                    <span class="mb-2 text-uppercase">{{auth()->user()->business_name}}</span>
                    <span class="mb-2">{{auth()->user()->phone}}</span>
                    @if (!empty(auth()->user()->verified_bvn))
                        <svg width="90" height="24" viewBox="0 0 90 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect width="90" height="24" rx="12" fill="#EDFFF7"/>
                            <path d="M18.1974 7.54545L20.658 14.9872H20.755L23.2109 7.54545H25.0945L21.7614 17H19.647L16.3185 7.54545H18.1974ZM26.3398 17V7.54545H32.4889V8.98118H28.0525V11.5479H32.1704V12.9837H28.0525V15.5643H32.5258V17H26.3398ZM34.2362 17V7.54545H37.7817C38.508 7.54545 39.1174 7.67164 39.6098 7.92401C40.1053 8.17637 40.4793 8.5303 40.7316 8.98579C40.9871 9.43821 41.1148 9.96603 41.1148 10.5692C41.1148 11.1755 40.9855 11.7018 40.727 12.1481C40.4716 12.5913 40.0945 12.9344 39.596 13.1776C39.0974 13.4176 38.4849 13.5376 37.7586 13.5376H35.2334V12.1158H37.5278C37.9525 12.1158 38.3003 12.0573 38.5711 11.9403C38.8419 11.8203 39.042 11.6464 39.1713 11.4187C39.3036 11.1879 39.3698 10.9047 39.3698 10.5692C39.3698 10.2338 39.3036 9.94756 39.1713 9.71058C39.0389 9.47053 38.8373 9.28894 38.5665 9.16584C38.2957 9.03965 37.9463 8.97656 37.5186 8.97656H35.949V17H34.2362ZM39.1205 12.7159L41.461 17H39.5498L37.2508 12.7159H39.1205ZM44.404 7.54545V17H42.6913V7.54545H44.404ZM46.2587 17V7.54545H52.3155V8.98118H47.9714V11.5479H51.9V12.9837H47.9714V17H46.2587ZM55.5886 7.54545V17H53.8759V7.54545H55.5886ZM57.4433 17V7.54545H63.5924V8.98118H59.156V11.5479H63.2739V12.9837H59.156V15.5643H63.6294V17H57.4433ZM68.5436 17H65.3398V7.54545H68.6082C69.5469 7.54545 70.3532 7.73473 71.0273 8.11328C71.7043 8.48875 72.2245 9.02888 72.5876 9.73366C72.9508 10.4384 73.1324 11.2817 73.1324 12.2635C73.1324 13.2483 72.9492 14.0947 72.583 14.8026C72.2198 15.5104 71.6951 16.0536 71.0088 16.4322C70.3256 16.8107 69.5038 17 68.5436 17ZM67.0525 15.5181H68.4605C69.1191 15.5181 69.6685 15.3981 70.1086 15.158C70.5487 14.9149 70.8795 14.5533 71.1011 14.0732C71.3227 13.59 71.4335 12.9867 71.4335 12.2635C71.4335 11.5402 71.3227 10.9401 71.1011 10.4631C70.8795 9.98295 70.5518 9.62441 70.1178 9.38743C69.6869 9.14737 69.1514 9.02734 68.5113 9.02734H67.0525V15.5181Z" fill="#008A4B"/>
                        </svg>
                    @else
                        <span><a style="font-weight: 700;" href="#">Verify Now</a></span>
                    @endif


                </div><!-- user-header -->
                <a href="#" class="dropdown-item"><i class="typcn typcn-user-outline"></i> My Profile</a>
                <a href="#" class="dropdown-item"><i class="typcn typcn-info-large-outline"></i> Inventory</a>
                <a href="#" class="dropdown-item"><i class="typcn typcn-key-outline"></i>Points and Rewards</a>
                <a href="#" class="dropdown-item"><i class="typcn typcn-cog-outline"></i> Business Settings</a>
                <a href="#" class="dropdown-item"><i class="typcn typcn-edit"></i> Security Settings</a>
                <a href="{{ route('logout') }}" class="dropdown-item"><i class="typcn typcn-key-outline"></i> Sign Out</a>
            </div><!--/.dropdown-menu -->
        </li>
    </ul>
  </div>
</nav><!--/.navbar-->

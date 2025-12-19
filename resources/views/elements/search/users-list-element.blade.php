    
    <div class="perfilCard">
        <a href="{{ route('profile', ['username' => $user->username]) }}">
            <div class="el-card is-always-shadow image-card">
                <div class="el-card__body card-hover">
                    <!-- Background image -->
                    <div class="background-image" style="background-image: url('{{ $user->avatar }}');"></div>
                    <!-- Overlay for dark effect -->
                    <div class="overlay"></div>
                    <!-- User Info -->
                    <div class="card-info">
                        <span class="name-perfil">
                            {{$user->name}}
                            @if($user->email_verified_at && $user->birthdate && ($user->verification && $user->verification->status == 'verified'))
                                <svg class="svg-inline--fa fa-badge-check font-awesome-icon verified-icon" aria-hidden="true" focusable="false" data-prefix="far" data-icon="badge-check" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="margin-left: 5px; color: #00f;">
                                    <path fill="currentColor" d="M200.3 81.5C210.9 61.5 231.9 48 256 48s45.1 13.5 55.7 33.5C317.1 91.7 329 96.6 340 93.2c21.6-6.6 46.1-1.4 63.1 15.7s22.3 41.5 15.7 63.1c-3.4 11 1.5 22.9 11.7 28.2c20 10.6 33.5 31.6 33.5 55.7s-13.5 45.1-33.5 55.7c-10.2 5.4-15.1 17.2-11.7 28.2c6.6 21.6 1.4 46.1-15.7 63.1s-41.5 22.3-63.1 15.7c-11-3.4-22.9 1.5-28.2 11.7c-10.6 20-31.6 33.5-55.7 33.5s-45.1-13.5-55.7-33.5c-5.4-10.2-17.2-15.1-28.2-11.7c-21.6 6.6-46.1 1.4-63.1-15.7S86.6 361.6 93.2 340c3.4-11-1.5-22.9-11.7-28.2C61.5 301.1 48 280.1 48 256s13.5-45.1 33.5-55.7C91.7 194.9 96.6 183 93.2 172c-6.6-21.6-1.4-46.1 15.7-63.1S150.4 86.6 172 93.2c11 3.4 22.9-1.5 28.2-11.7zM256 0c-35.9 0-67.8 17-88.1 43.4c-33-4.3-67.6 6.2-93 31.6s-35.9 60-31.6 93C17 188.2 0 220.1 0 256s17 67.8 43.4 88.1c-4.3 33 6.2 67.6 31.6 93s60 35.9 93 31.6C188.2 495 220.1 512 256 512s67.8-17 88.1-43.4c33 4.3 67.6-6.2 93-31.6s35.9-60 31.6-93C495 323.8 512 291.9 512 256s-17-67.8-43.4-88.1c4.3-33-6.2-67.6-31.6-93s-60-35.9-93-31.6C323.8 17 291.9 0 256 0zM369 209c9.4-9.4 9.4-24.6 0-33.9s-24.6-9.4-33.9 0l-111 111-47-47c-9.4-9.4-24.6-9.4-33.9 0s-9.4 24.6 0 33.9l64 64c9.4 9.4 24.6 9.4 33.9 0L369 209z"></path>
                                </svg>
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        </a>
        <div class="form-group text-dark">
            <div class="el-overlay" style="z-index: 2006; display: none;">
                <div role="dialog" aria-modal="true" aria-label="AVISO" aria-describedby="el-id-4720-8" class="el-overlay-dialog"></div>
            </div>
        </div>
    </div>
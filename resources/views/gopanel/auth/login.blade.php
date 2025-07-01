<!doctype html>
<html lang="en">
<head>
        <meta charset="utf-8" />
        <title>Gopanel | Daxil ol</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta content="Qragte.az skan et kecid et" name="description" />
        <meta content="Proweb" name="author" />
        <!-- App favicon -->
        <link rel="shortcut icon" href="/assets/gopanel/images/favicon.ico">
        @include('gopanel.assets.styles')
    </head>

    <body>
        <div class="account-pages my-5 pt-sm-5">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6 col-xl-5">
                        <div class="card overflow-hidden">
                            <div class="bg-primary bg-soft">
                                <div class="row">
                                    <div class="col-7">
                                        <div class="text-primary p-4">
                                            <h5 class="text-primary">Xoş gəlmisiniz!</h5>
                                            <p>Gopanel ilə davam etmək üçün daxil olun.</p>
                                        </div>
                                    </div>
                                    <div class="col-5 align-self-end">
                                        <img src="/assets/gopanel/images/profile-img.png" alt="" class="img-fluid">
                                    </div>
                                </div>
                            </div>
                            <div class="card-body pt-0"> 
                                <div class="auth-logo">
                                    <a href="{{route("gopanel.auth.login")}}" class="auth-logo-light">
                                        <div class="avatar-md profile-user-wid mb-4">
                                            <span class="avatar-title rounded-circle bg-light">
                                                <img src="/assets/gopanel/images/logo-light.svg" alt="" class="rounded-circle" height="34">
                                            </span>
                                        </div>
                                    </a>

                                    <a href="{{route("gopanel.auth.login")}}" class="auth-logo-dark">
                                        <div class="avatar-md profile-user-wid mb-4">
                                            <span class="avatar-title rounded-circle bg-light">
                                                <img src="/assets/gopanel/images/logo.svg" alt="" class="rounded-circle" height="34">
                                            </span>
                                        </div>
                                    </a>
                                </div>
                                <div class="p-2">
                                    <form class="form-horizontal" id="gopanelAuthFrom" action="{{route("gopanel.auth.login")}}">
        
                                        <div class="mb-3">
                                            <label for="email" class="form-label">E-poçt</label>
                                            <input type="email" name="email" class="form-control" id="email" placeholder="E-poçt daxil edin">
                                        </div>
                
                                        <div class="mb-3">
                                            <label class="form-label">Şifrə</label>
                                            <div class="input-group auth-pass-inputgroup">
                                                <input type="password" name="password" class="form-control" placeholder="Şifrə daxil edin" aria-label="Password" id="password" aria-describedby="password-addon">
                                                <button class="btn btn-light " type="button" id="password-addon"><i class="mdi mdi-eye-outline"></i></button>
                                            </div>
                                        </div>

                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="remember-check">
                                            <label class="form-check-label" for="remember-check">
                                                Məni xatırla
                                            </label>
                                        </div>
                                        
                                        <div class="mt-3 d-grid">
                                            <button class="btn btn-primary waves-effect waves-light" type="submit">Daxil ol</button>
                                        </div>
                                    </form>
                                </div>
            
                            </div>
                        </div>
                        <div class="mt-5 text-center">
                            
                            <div>
                                {{-- <p>Don't have an account ? <a href="auth-register.html" class="fw-medium text-primary"> Signup now </a> </p> --}}
                                <p>© <script>document.write(new Date().getFullYear())</script> Gopanel. ilə hazırlanmışdır <i class="mdi mdi-heart text-danger"></i> by Proweb</p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
        <!-- end account-pages -->
        <!-- JAVASCRIPT -->
        @include('gopanel.assets.scripts');
        <script src="{{asset("assets/gopanel/js/auth.js")}}"></script>
    </body>
</html>

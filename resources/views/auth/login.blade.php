@extends('layouts.app')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400;1,600&family=Playfair+Display:wght@500;700;900&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#0c0a09; --bg2:#14110f; --cream:#f3e9d8; --gold:#c9a45c; --gold2:#e8cf94;
  --wine:#6d1a2a; --emerald:#0f3d33; --muted:#a79d8d; --bad:#d9556a; --line:rgba(201,164,92,.18);
}
/* layout'dagi oq navbar va fonni yashiramiz (faqat shu sahifada) */
body{background:var(--bg)!important;color:var(--cream);font-family:'Inter',sans-serif;font-weight:300;overflow-x:hidden}
.navbar{display:none!important}
main.py-4{padding:0!important}

body::before{content:"";position:fixed;inset:0;z-index:5;pointer-events:none;opacity:.06;
  background-image:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='160' height='160'><filter id='n'><feTurbulence type='fractalNoise' baseFrequency='.9' numOctaves='2'/></filter><rect width='100%' height='100%' filter='url(%23n)'/></svg>")}
#spot{position:fixed;inset:0;z-index:4;pointer-events:none;background:radial-gradient(520px circle at var(--x,50%) var(--y,30%),rgba(201,164,92,.12),transparent 60%)}

.lg{position:relative;min-height:100vh;display:grid;place-items:center;padding:30px 18px;overflow:hidden;
  background:radial-gradient(ellipse at 75% 40%,#2a1a14 0%,transparent 60%),radial-gradient(ellipse at 10% 100%,#0f3d33 0%,transparent 55%),var(--bg)}
.orb{position:absolute;border-radius:50%;filter:blur(90px);pointer-events:none}
.o1{width:380px;height:380px;background:var(--wine);left:-100px;top:12%;opacity:.45;animation:fl 14s ease-in-out infinite alternate}
.o2{width:320px;height:320px;background:var(--gold);right:6%;bottom:2%;opacity:.16;animation:fl 12s ease-in-out infinite alternate-reverse}
.o3{width:260px;height:260px;background:var(--emerald);left:42%;top:-70px;opacity:.7;animation:fl 17s ease-in-out infinite alternate}
@keyframes fl{to{transform:translate(40px,-30px) scale(1.15)}}

.wrap{position:relative;z-index:10;width:100%;max-width:440px;perspective:1400px}
.brand{text-align:center;margin-bottom:28px;animation:rise .9s cubic-bezier(.2,.8,.2,1) both}
.brand .logo{font-family:'Playfair Display',serif;font-size:2rem;letter-spacing:.4em;color:var(--gold2);padding-left:.4em}
.brand small{display:block;font-size:.62rem;letter-spacing:.5em;text-transform:uppercase;color:var(--gold);margin-top:6px}
@keyframes rise{from{opacity:0;transform:translateY(40px)}}

.lcard{position:relative;padding:44px 40px 38px;background:linear-gradient(160deg,rgba(255,255,255,.06),rgba(255,255,255,.01));
  border:1px solid var(--line);backdrop-filter:blur(16px);box-shadow:0 40px 100px rgba(0,0,0,.65);
  transform-style:preserve-3d;transition:transform .2s ease-out,border-color .4s;animation:rise 1s .15s cubic-bezier(.2,.8,.2,1) both}
.lcard:hover{border-color:var(--gold)}
.lcard::before,.lcard::after{content:"";position:absolute;width:26px;height:26px;border:1px solid var(--gold);pointer-events:none}
.lcard::before{top:-1px;left:-1px;border-right:0;border-bottom:0}
.lcard::after{bottom:-1px;right:-1px;border-left:0;border-top:0}
.glow{position:absolute;inset:0;pointer-events:none;opacity:0;transition:.3s;background:radial-gradient(300px circle at var(--mx,50%) var(--my,50%),rgba(232,207,148,.13),transparent 60%)}
.lcard:hover .glow{opacity:1}

.lcard h1{font-family:'Playfair Display',serif;font-weight:500;font-size:2rem;line-height:1.15;margin-bottom:6px;transform:translateZ(30px)}
.lcard h1 em{font-family:'Cormorant Garamond',serif;font-style:italic;color:var(--gold2);font-weight:400}
.lcard .sub{color:var(--muted);font-size:.88rem;margin-bottom:34px;transform:translateZ(20px)}

.fld{position:relative;margin-bottom:26px}
.fld input.fi{width:100%;background:none;border:0;border-bottom:1px solid rgba(201,164,92,.4);border-radius:0;padding:16px 38px 10px 0;color:var(--cream);font:inherit;font-size:1rem;outline:none;transition:.3s;box-shadow:none}
.fld input.fi:focus{border-color:var(--gold2);background:none;color:var(--cream);box-shadow:none}
.fld input.fi:-webkit-autofill{-webkit-text-fill-color:var(--cream);-webkit-box-shadow:0 0 0 40px #14110f inset}
.fld label.fl{position:absolute;left:0;top:14px;font-size:.7rem;letter-spacing:.25em;text-transform:uppercase;color:var(--muted);pointer-events:none;transition:.3s}
.fld input.fi:focus+label.fl,.fld input.fi:not(:placeholder-shown)+label.fl{top:-12px;color:var(--gold)}
.fld::after{content:"";position:absolute;left:0;bottom:0;height:1px;width:0;background:var(--gold2);transition:.5s cubic-bezier(.2,.8,.2,1)}
.fld:focus-within::after{width:100%}
.fld.err input.fi{border-color:var(--bad)}
.eye{position:absolute;right:0;top:12px;background:none;border:0;color:var(--muted);font-size:1.05rem;cursor:pointer;transition:.3s;padding:0 2px}
.eye:hover{color:var(--gold2)}
.msg{display:block;color:var(--bad);font-size:.78rem;margin-top:8px}

.opts{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin:-4px 0 30px;font-size:.82rem}
.chk{display:flex;align-items:center;gap:10px;cursor:pointer;color:var(--muted);user-select:none}
.chk input{display:none}
.chk i{width:18px;height:18px;border:1px solid rgba(201,164,92,.5);display:grid;place-items:center;transition:.3s;font-style:normal;font-size:.7rem;color:transparent}
.chk input:checked+i{background:var(--gold);color:#0c0a09;border-color:var(--gold)}
.opts a{color:var(--gold2);text-decoration:none;position:relative;transition:.3s}
.opts a:hover{color:var(--cream)}

.btn-lg{width:100%;display:flex;justify-content:center;align-items:center;gap:12px;padding:16px;border:1px solid var(--gold);background:var(--gold);color:#0c0a09;
  font:inherit;font-size:.74rem;letter-spacing:.32em;text-transform:uppercase;font-weight:500;position:relative;overflow:hidden;cursor:pointer;transition:.5s;z-index:0}
.btn-lg::before{content:"";position:absolute;inset:0;background:var(--cream);transform:translateY(101%);transition:.5s cubic-bezier(.2,.8,.2,1);z-index:-1}
.btn-lg:hover::before{transform:none}
.btn-lg:disabled{opacity:.7;cursor:wait}
.btn-lg .sp{width:14px;height:14px;border:2px solid rgba(0,0,0,.3);border-top-color:#0c0a09;border-radius:50%;animation:spin .7s linear infinite;display:none}
.btn-lg.load .sp{display:inline-block}
@keyframes spin{to{transform:rotate(360deg)}}

.alert-box{border:1px solid rgba(217,85,106,.45);background:rgba(217,85,106,.08);color:#f0a3b0;padding:12px 16px;font-size:.84rem;margin-bottom:24px}

.foot{text-align:center;margin-top:26px;font-size:.66rem;letter-spacing:.3em;text-transform:uppercase;color:var(--muted);animation:rise 1s .3s both}
.foot a{color:var(--gold2);text-decoration:none}
@media(max-width:480px){.lcard{padding:36px 24px 30px}}
@media (prefers-reduced-motion:reduce){*{animation-duration:.01s!important;transition-duration:.01s!important}}
</style>

<div id="spot"></div>
<div class="lg">
    <div class="orb o1"></div><div class="orb o2"></div><div class="orb o3"></div>

    <div class="wrap">
        <div class="brand">
            <div class="logo">ZARAFSHON</div>
            <small>Admin panel</small>
        </div>

        <div class="lcard" id="lcard">
            <div class="glow"></div>

            <h1>Xush kelibsiz, <em>boshliq</em></h1>
            <p class="sub">Davom etish uchun hisobingizga kiring.</p>

            @if (session('status'))
                <div class="alert-box" style="border-color:rgba(79,191,143,.45);background:rgba(79,191,143,.08);color:#9fe0c1">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}" id="loginForm">
                @csrf

                <div class="fld @error('email') err @enderror">
                    <input id="email" type="email" class="fi" name="email" value="{{ old('email') }}" placeholder=" " required autocomplete="email" autofocus>
                    <label class="fl" for="email">{{ __('Email Address') }}</label>
                    @error('email')
                        <span class="msg" role="alert">{{ $message }}</span>
                    @enderror
                </div>

                <div class="fld @error('password') err @enderror">
                    <input id="password" type="password" class="fi" name="password" placeholder=" " required autocomplete="current-password">
                    <label class="fl" for="password">{{ __('Password') }}</label>
                    <button type="button" class="eye" id="eye" aria-label="Parolni ko'rsatish">👁</button>
                    @error('password')
                        <span class="msg" role="alert">{{ $message }}</span>
                    @enderror
                </div>

                <div class="opts">
                    <label class="chk">
                        <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                        <i>✓</i>{{ __('Remember Me') }}
                    </label>

                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}">{{ __('Forgot Your Password?') }}</a>
                    @endif
                </div>

                <button type="submit" class="btn-lg" id="btn">
                    <span class="sp"></span><span>{{ __('Login') }}</span>
                </button>
            </form>
        </div>

        <div class="foot">© {{ date('Y') }} ZARAFSHON · <a href="{{ url('/') }}">← Saytga qaytish</a></div>
    </div>
</div>

<script>
(function(){
    /* spotlight */
    addEventListener('mousemove',function(e){
        document.documentElement.style.setProperty('--x',e.clientX+'px');
        document.documentElement.style.setProperty('--y',e.clientY+'px');
    });

    /* 3D tilt */
    var c=document.getElementById('lcard');
    c.addEventListener('mousemove',function(e){
        var r=c.getBoundingClientRect(),px=(e.clientX-r.left)/r.width,py=(e.clientY-r.top)/r.height;
        c.style.transform='rotateY('+((px-.5)*7)+'deg) rotateX('+(-(py-.5)*7)+'deg)';
        c.style.setProperty('--mx',px*100+'%');c.style.setProperty('--my',py*100+'%');
    });
    c.addEventListener('mouseleave',function(){c.style.transform=''});

    /* parolni ko'rsatish / yashirish */
    var p=document.getElementById('password'),eye=document.getElementById('eye');
    eye.addEventListener('click',function(){
        var show=p.type==='password';
        p.type=show?'text':'password';
        eye.textContent=show?'🙈':'👁';
    });

    /* yuborilayotganda tugmada spinner */
    document.getElementById('loginForm').addEventListener('submit',function(){
        var b=document.getElementById('btn');
        b.classList.add('load');
        setTimeout(function(){b.disabled=true},0);
    });
})();
</script>
@endsection

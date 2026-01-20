<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>

    <style type="text/tailwindcss">
        @layer components {
            .glass-panel { 
                background: rgba(255, 255, 255, 0.7); 
                backdrop-filter: blur(16px) saturate(180%); 
                border: 1px solid rgba(255, 255, 255, 0.5); 
                box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.08); 
            }
            .input-field {
                @apply w-full px-4 py-3 bg-white/50 border border-slate-200 rounded-2xl outline-none text-sm font-semibold text-slate-700 transition-all;
            }
            .input-field:focus {
                @apply ring-4 ring-blue-500/10 border-blue-500 bg-white;
            }
        }
        body {
            background-color: #f8fafc;
            background-image: 
                radial-gradient(at 0% 0%, hsla(222,47%,91%,1) 0, transparent 50%), 
                radial-gradient(at 100% 0%, hsla(217,91%,95%,1) 0, transparent 50%),
                radial-gradient(at 50% 100%, hsla(225,39%,95%,1) 0, transparent 50%);
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-6">

    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-xl shadow-blue-500/30 mb-5 transform -rotate-3">
                <span class="material-symbols-outlined text-3xl font-bold">shield_person</span>
            </div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tight">System Login</h1>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] mt-2">Secure Access Portal</p>
        </div>

        <div class="glass-panel rounded-[2.5rem] p-10 relative overflow-hidden">
            <div class="absolute -top-24 -right-24 w-48 h-48 bg-blue-400/10 blur-3xl rounded-full"></div>

            <form action="{{ route('login') }}" method="POST" class="space-y-6 relative">
                @csrf
                
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2.5 ml-1">Username</label>
                    <div class="relative">
                        <input type="text" name="username" value="{{ old('username') }}" required autofocus
                               class="input-field placeholder:text-slate-300 uppercase tracking-tight" 
                               placeholder="Enter your username">
                    </div>
                    @error('username')
                        <div class="flex items-center gap-1.5 mt-2 ml-1 text-rose-500">
                            <span class="material-symbols-outlined text-[14px] font-bold">error</span>
                            <p class="text-[10px] font-bold uppercase tracking-wider">{{ $message }}</p>
                        </div>
                    @enderror
                </div>

                <div>
                    <div class="flex justify-between mb-2.5 ml-1">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Password</label>
                        <a href="#" class="text-[10px] font-black text-blue-600 uppercase tracking-widest hover:text-blue-700 transition-colors">Forgot Key?</a>
                    </div>
                    <input type="password" name="password" required
                           class="input-field placeholder:text-slate-300" 
                           placeholder="••••••••••••">
                </div>

                <div class="flex items-center gap-3 ml-1">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="remember" class="sr-only peer">
                        <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                        <span class="ml-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">Keep me signed in</span>
                    </label>
                </div>

                <button type="submit" 
                        class="w-full py-4 bg-slate-900 text-white rounded-2xl font-black uppercase text-xs tracking-[0.2em] shadow-2xl shadow-slate-900/30 hover:bg-slate-800 transition-all hover:-translate-y-0.5 active:scale-[0.98] flex items-center justify-center gap-2 group">
                    <span>Authenticate</span>
                    <span class="material-symbols-outlined text-[18px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
                </button>
            </form>
        </div>

        <div class="mt-10 flex flex-col items-center gap-4">
            <div class="h-px w-12 bg-slate-200"></div>
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-[0.4em]">
                Verified Infrastructure &bull; 2026
            </p>
        </div>
    </div>

</body>
</html>
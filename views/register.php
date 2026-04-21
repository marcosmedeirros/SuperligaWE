<div class="min-h-screen flex items-center justify-center p-4 relative overflow-hidden">

    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-green-500/5 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-green-500/5 rounded-full blur-3xl"></div>
    </div>

    <div class="w-full max-w-md z-10">

        <!-- logo -->
        <div class="text-center mb-8">
            <a href="?page=login" class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-br from-green-500 to-green-700 shadow-lg shadow-green-500/30 mb-4">
                <i class="fas fa-futbol text-white text-3xl"></i>
            </a>
            <h1 class="text-5xl font-black tracking-tight">
                <span class="text-white">ECO</span><span class="text-green-400">FUT</span>
            </h1>
            <p class="text-slate-400 mt-1 text-sm">O seu simulador de futebol</p>
        </div>

        <!-- card -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-8 shadow-2xl">
            <h2 class="text-xl font-bold text-white mb-6">Criar conta</h2>

            <?php if ($msg && $msg_tipo === 'erro'): ?>
                <div class="flex items-center gap-2 bg-red-500/10 border border-red-500/30 text-red-400 rounded-xl px-4 py-3 mb-5 text-sm">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($msg) ?>
                </div>
            <?php endif; ?>

            <form action="?page=register" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="register">

                <div>
                    <label class="block text-sm text-slate-400 mb-1.5">Seu nome</label>
                    <div class="relative">
                        <i class="fas fa-user absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 text-sm"></i>
                        <input type="text" name="nome" required minlength="2" autocomplete="name"
                            value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>"
                            class="w-full bg-slate-800 border border-slate-700 rounded-xl pl-10 pr-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-green-500 focus:ring-1 focus:ring-green-500 transition"
                            placeholder="João Silva">
                    </div>
                </div>

                <div>
                    <label class="block text-sm text-slate-400 mb-1.5">E-mail</label>
                    <div class="relative">
                        <i class="fas fa-envelope absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 text-sm"></i>
                        <input type="email" name="email" required autocomplete="email"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                            class="w-full bg-slate-800 border border-slate-700 rounded-xl pl-10 pr-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-green-500 focus:ring-1 focus:ring-green-500 transition"
                            placeholder="seu@email.com">
                    </div>
                </div>

                <div>
                    <label class="block text-sm text-slate-400 mb-1.5">Senha <span class="text-slate-600">(mín. 6 caracteres)</span></label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 text-sm"></i>
                        <input type="password" name="senha" required minlength="6" autocomplete="new-password"
                            class="w-full bg-slate-800 border border-slate-700 rounded-xl pl-10 pr-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-green-500 focus:ring-1 focus:ring-green-500 transition"
                            placeholder="••••••••">
                    </div>
                </div>

                <div>
                    <label class="block text-sm text-slate-400 mb-1.5">Confirmar senha</label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 text-sm"></i>
                        <input type="password" name="confirmar_senha" required minlength="6" autocomplete="new-password"
                            class="w-full bg-slate-800 border border-slate-700 rounded-xl pl-10 pr-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-green-500 focus:ring-1 focus:ring-green-500 transition"
                            placeholder="••••••••">
                    </div>
                </div>

                <button type="submit"
                    class="w-full bg-green-600 hover:bg-green-500 active:bg-green-700 text-white font-bold py-3 rounded-xl transition-all shadow-lg shadow-green-500/20 mt-2">
                    Criar minha conta <i class="fas fa-user-plus ml-1"></i>
                </button>
            </form>
        </div>

        <p class="text-center text-slate-500 text-sm mt-6">
            Já tem conta?
            <a href="?page=login" class="text-green-400 hover:text-green-300 font-semibold transition">Fazer login</a>
        </p>
    </div>
</div>

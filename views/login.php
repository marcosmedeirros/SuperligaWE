<div class="flex-1 flex items-center justify-center p-4">
    <form action="index.php" method="POST" class="bg-slate-800 p-8 rounded-2xl w-full max-w-md shadow-2xl border border-slate-700">
        <h1 class="text-3xl font-black text-emerald-400 text-center mb-6">FANTASY FC</h1>
        <?php if ($msg) echo "<p class='text-red-400 text-center mb-4 text-sm'>$msg</p>"; ?>
        <input type="hidden" name="action" value="login">
        <input type="email" name="email" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 mb-4 text-white" placeholder="admin@admin.com">
        <input type="password" name="senha" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 mb-6 text-white" placeholder="123456">
        <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-3 rounded-xl transition">ENTRAR</button>
    </form>
</div>

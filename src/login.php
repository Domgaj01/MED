<?php
require_once __DIR__ . "/parts/session.php";
require_once __DIR__ . "/parts/db.php";

if (logged_in()) {
    redirect_to("index.php");
}

$message = isset($_GET["logout"]) ? "You are now logged out." : "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $password === "") {
        $message = "Enter your username and password.";
    } else {
        $stmt = $conn->prepare("SELECT user_id, username, password_hash, role FROM users WHERE username = ? AND is_active = 1 LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->bind_result($userId, $foundUsername, $hashedPassword, $role);

        if ($stmt->fetch() && password_verify($password, $hashedPassword)) {
            $stmt->close();
            $stmt = $conn->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stmt->close();

            session_regenerate_id(true);
            $_SESSION["user_id"] = $userId;
            $_SESSION["username"] = $foundUsername;
            $_SESSION["role"] = $role;
            redirect_to("index.php");
        }

        $stmt->close();
        $message = "Username/password combination incorrect.";
    }
}
?>
<?php include __DIR__ . "/header.php"; ?>

<main class="mx-auto grid min-h-[650px] max-w-[1440px] items-center gap-12 px-[clamp(24px,6vw,96px)] py-14 lg:grid-cols-2 lg:gap-20">
    <section class="relative hidden min-h-[420px] overflow-hidden rounded-3xl bg-[#140b0b] p-10 lg:flex lg:flex-col lg:justify-center xl:p-14" aria-label="Welcome to Mystery Cinema">
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_20%_25%,rgba(185,5,4,0.3),transparent_42%),linear-gradient(145deg,#1b1010_0%,#0b0b0b_72%)]"></div>
        <div class="absolute -bottom-24 -right-16 h-[28rem] w-[28rem] rounded-full border border-white/[0.06]"></div>
        <div class="absolute -bottom-12 -right-4 h-[24rem] w-[24rem] rounded-full border border-white/[0.06]"></div>
        <div class="relative z-10">
            <span class="mb-6 inline-flex items-center gap-2 rounded-full border border-red-400/20 bg-red-400/[0.08] px-3.5 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-red-200">
                <span class="h-1.5 w-1.5 rounded-full bg-red-400"></span>
                Your seat is waiting
            </span>
            <h1 class="text-4xl font-black uppercase leading-[1.05] tracking-[-0.06em] xl:text-5xl">Every great<br>night starts<br><span class="text-[#ef5552]">with a story.</span></h1>
            <p class="mt-6 max-w-md text-base leading-7 text-white/60">Sign in to pick up where the credits left off. Your next cinema moment is just around the corner.</p>
        </div>
    </section>

    <section class="mx-auto w-full max-w-md">
        <div class="mb-10">
            <p class="mb-3 text-xs font-bold uppercase tracking-[0.2em] text-[#ef5552]">Welcome back</p>
            <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">Sign in</h2>
            <p class="mt-3 text-sm leading-6 text-white/50">Enter your details to access your account.</p>
        </div>

        <?php if ($message !== ""): ?>
            <div class="mb-6 flex gap-3 rounded-xl border border-red-400/20 bg-red-400/[0.08] p-4 text-sm leading-6 text-red-100" role="alert">
                <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-red-300/40 text-xs font-bold" aria-hidden="true">!</span>
                <p><?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?></p>
            </div>
        <?php endif; ?>

        <form action="login.php" method="post" class="space-y-5">
            <label class="block text-sm font-medium text-white/80" for="username">
                Username
                <input class="mt-2.5 h-12 w-full rounded-xl border border-white/10 bg-white/[0.04] px-4 text-sm text-white outline-none transition placeholder:text-white/25 hover:border-white/20 focus:border-red-400/70 focus:ring-4 focus:ring-red-500/10" id="username" name="username" type="text" maxlength="50" autocomplete="username" placeholder="Your username" required>
            </label>
            <label class="block text-sm font-medium text-white/80" for="password">
                Password
                <input class="mt-2.5 h-12 w-full rounded-xl border border-white/10 bg-white/[0.04] px-4 text-sm text-white outline-none transition placeholder:text-white/25 hover:border-white/20 focus:border-red-400/70 focus:ring-4 focus:ring-red-500/10" id="password" name="password" type="password" autocomplete="current-password" placeholder="Your password" required>
            </label>
            <button class="group flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-[#b90504] px-5 text-sm font-bold text-white shadow-lg shadow-red-950/30 transition hover:bg-[#d20b09] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-red-300" type="submit">
                Sign in <span class="transition-transform group-hover:translate-x-1" aria-hidden="true">&rarr;</span>
            </button>
        </form>

        <p class="mt-8 text-center text-sm text-white/45">New to Mystery Cinema? <a class="font-semibold text-white transition hover:text-[#ff7774]" href="create_user.php">Create an account</a></p>
        <div class="mt-8 flex items-center justify-center gap-2 text-xs text-white/30">
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M6 8V5.75a4 4 0 1 1 8 0V8m-9 0h10a1 1 0 0 1 1 1v6a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
            Your account is protected and secure
        </div>
    </section>
</main>

<?php include __DIR__ . "/footer.php"; ?>
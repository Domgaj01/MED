<?php
require_once __DIR__ . "/parts/session.php";
require_once __DIR__ . "/parts/db.php";

if (logged_in()) {
    redirect_to("index.php");
}

$message = "";
$success = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    if ($username === "" || strlen($username) > 50) {
        $message = "Enter a username of 1 to 50 characters.";
    } elseif (strlen($password) < 8 || strlen($password) > 72) {
        $message = "Use a password between 8 and 72 characters.";
    } elseif ($password !== $confirmPassword) {
        $message = "The passwords do not match.";
    } else {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
        $stmt->bind_param("ss", $username, $passwordHash);

        try {
            $stmt->execute();
            $success = true;
            $message = "Account created. You can now log in.";
        } catch (mysqli_sql_exception $exception) {
            if ($exception->getCode() !== 1062) {
                throw $exception;
            }
            $message = "That username is already in use.";
        } finally {
            $stmt->close();
        }
    }
}
?>
<?php include __DIR__ . "/header.php"; ?>

<main class="mx-auto grid min-h-[650px] max-w-[1440px] items-center gap-12 px-[clamp(24px,6vw,96px)] py-14 lg:grid-cols-2 lg:gap-20">
    <section class="relative hidden min-h-[420px] overflow-hidden rounded-3xl bg-[#140b0b] p-10 lg:flex lg:flex-col lg:justify-center xl:p-14" aria-label="Join Mystery Cinema">
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_20%_25%,rgba(185,5,4,0.3),transparent_42%),linear-gradient(145deg,#1b1010_0%,#0b0b0b_72%)]"></div>
        <div class="absolute -bottom-24 -right-16 h-[28rem] w-[28rem] rounded-full border border-white/[0.06]"></div>
        <div class="absolute -bottom-12 -right-4 h-[24rem] w-[24rem] rounded-full border border-white/[0.06]"></div>
        <div class="relative z-10">
            <span class="mb-6 inline-flex items-center gap-2 rounded-full border border-red-400/20 bg-red-400/[0.08] px-3.5 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-red-200">
                <span class="h-1.5 w-1.5 rounded-full bg-red-400"></span>
                A new story begins
            </span>
            <h1 class="text-4xl font-black uppercase leading-[1.05] tracking-[-0.06em] xl:text-5xl">Your next<br>favorite night<br><span class="text-[#ef5552]">starts here.</span></h1>
            <p class="mt-6 max-w-md text-base leading-7 text-white/60">Join Mystery Cinema and make room for films, surprises, and nights worth remembering.</p>
        </div>
    </section>

    <section class="mx-auto w-full max-w-md">
        <div class="mb-8">
            <p class="mb-3 text-xs font-bold uppercase tracking-[0.2em] text-[#ef5552]">Become a member</p>
            <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">Create account</h2>
            <p class="mt-3 text-sm leading-6 text-white/50">A few details and you’re ready for your next movie night.</p>
        </div>

        <?php if ($message !== ""): ?>
            <div class="mb-6 flex gap-3 rounded-xl border <?= $success ? "border-emerald-400/20 bg-emerald-400/[0.08] text-emerald-100" : "border-red-400/20 bg-red-400/[0.08] text-red-100" ?> p-4 text-sm leading-6" role="<?= $success ? "status" : "alert" ?>">
                <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-current/40 text-xs font-bold" aria-hidden="true"><?= $success ? "✓" : "!" ?></span>
                <p><?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?></p>
            </div>
        <?php endif; ?>

        <?php if (!$success): ?>
            <form action="create_user.php" method="post" class="space-y-4">
                <label class="block text-sm font-medium text-white/80" for="username">
                    Username
                    <input class="mt-2.5 h-12 w-full rounded-xl border border-white/10 bg-white/[0.04] px-4 text-sm text-white outline-none transition placeholder:text-white/25 hover:border-white/20 focus:border-red-400/70 focus:ring-4 focus:ring-red-500/10" id="username" name="username" type="text" maxlength="50" autocomplete="username" placeholder="Choose a username" required>
                </label>
                <label class="block text-sm font-medium text-white/80" for="password">
                    Password
                    <input class="mt-2.5 h-12 w-full rounded-xl border border-white/10 bg-white/[0.04] px-4 text-sm text-white outline-none transition placeholder:text-white/25 hover:border-white/20 focus:border-red-400/70 focus:ring-4 focus:ring-red-500/10" id="password" name="password" type="password" minlength="8" maxlength="72" autocomplete="new-password" placeholder="At least 8 characters" required>
                </label>
                <label class="block text-sm font-medium text-white/80" for="confirm_password">
                    Confirm password
                    <input class="mt-2.5 h-12 w-full rounded-xl border border-white/10 bg-white/[0.04] px-4 text-sm text-white outline-none transition placeholder:text-white/25 hover:border-white/20 focus:border-red-400/70 focus:ring-4 focus:ring-red-500/10" id="confirm_password" name="confirm_password" type="password" minlength="8" maxlength="72" autocomplete="new-password" placeholder="Enter your password again" required>
                </label>
                <p class="pt-1 text-xs leading-5 text-white/35">Passwords must be 8–72 characters. Your password is securely hashed.</p>
                <button class="group flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-[#b90504] px-5 text-sm font-bold text-white shadow-lg shadow-red-950/30 transition hover:bg-[#d20b09] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-red-300" type="submit">
                    Create account <span class="transition-transform group-hover:translate-x-1" aria-hidden="true">&rarr;</span>
                </button>
            </form>
        <?php else: ?>
            <a class="group flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-[#b90504] px-5 text-sm font-bold text-white shadow-lg shadow-red-950/30 transition hover:bg-[#d20b09] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-red-300" href="login.php">
                Continue to sign in <span class="transition-transform group-hover:translate-x-1" aria-hidden="true">&rarr;</span>
            </a>
        <?php endif; ?>

        <p class="mt-8 text-center text-sm text-white/45">Already have an account? <a class="font-semibold text-white transition hover:text-[#ff7774]" href="login.php">Sign in</a></p>
    </section>
</main>

<?php include __DIR__ . "/footer.php"; ?>
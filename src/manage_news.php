<?php
require_once __DIR__ . "/parts/admin_auth.php";
require_once __DIR__ . "/parts/db.php";
require_once __DIR__ . "/classes/AdminPageController.php";
require_once __DIR__ . "/classes/NewsAdminController.php";

$page = new NewsAdminController($conn, $_GET, $_POST, $_SERVER["REQUEST_METHOD"]);
$message = $page->message;
$error = $page->error;
$csrfToken = $page->csrfToken;
$articles = $page->articles;
$article = $page->article;
$articleId = $page->articleId;
$isCreating = $page->isCreating;
?>
<?php include __DIR__ . "/header.php"; ?>

<main class="mx-auto min-h-[650px] max-w-4xl px-6 py-12">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold">Manage news</h1>
            <p class="mt-2 text-sm text-white/60">Create, edit, publish, and remove news articles.</p>
        </div>
        <a class="inline-flex rounded-lg bg-accent px-5 py-3 text-sm font-bold text-white transition-colors hover:bg-accent-dark" href="manage_news.php?create=1">Create article</a>
    </div>

    <?php if ($message !== ""): ?>
        <p class="mt-6 rounded-xl border border-emerald-400/20 bg-emerald-400/10 p-4 text-sm text-emerald-100" role="status"><?= AdminPageController::escape($message) ?></p>
    <?php endif; ?>
    <?php if ($error !== ""): ?>
        <p class="mt-6 rounded-xl border border-red-400/20 bg-red-400/10 p-4 text-sm text-red-100" role="alert"><?= AdminPageController::escape($error) ?></p>
    <?php endif; ?>

    <?php if ($article !== null): ?>
        <form action="manage_news.php" method="post" class="mt-8 space-y-5 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <input type="hidden" name="csrf_token" value="<?= AdminPageController::escape($csrfToken) ?>">
            <input type="hidden" name="action" value="<?= $isCreating ? "create" : "update" ?>">
            <?php if (!$isCreating): ?>
                <input type="hidden" name="news_id" value="<?= (int) $articleId ?>">
            <?php endif; ?>

            <h2 class="text-xl font-bold"><?= $isCreating ? "Create article" : "Edit article" ?></h2>
            <label class="block text-sm font-medium" for="news-title">
                Title
                <input class="mt-2 h-12 w-full rounded-lg border border-white/15 bg-[#1a1a1a] px-3 text-background" id="news-title" name="title" maxlength="200" value="<?= AdminPageController::escape($article["title"]) ?>" required>
            </label>
            <label class="block text-sm font-medium" for="news-body">
                Article text
                <textarea class="mt-2 min-h-48 w-full rounded-lg border border-white/15 bg-[#1a1a1a] p-3 text-background" id="news-body" name="body" maxlength="60000" required><?= AdminPageController::escape($article["body"]) ?></textarea>
            </label>
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_published" value="1" <?= (int) $article["is_published"] === 1 ? "checked" : "" ?>>
                Publish on the home page
            </label>
            <div class="flex flex-wrap gap-3">
                <button class="rounded-lg bg-accent px-6 py-3 text-sm font-bold text-white transition-colors hover:bg-accent-dark" type="submit"><?= $isCreating ? "Create article" : "Save article" ?></button>
                <a class="rounded-lg border border-white/15 px-6 py-3 text-sm font-semibold hover:bg-white/5" href="manage_news.php">Cancel</a>
            </div>
        </form>
    <?php endif; ?>

    <section class="mt-10" aria-labelledby="news-list-title">
        <h2 class="text-xl font-bold" id="news-list-title">Articles</h2>
        <p class="mt-2 text-sm text-white/60">Published articles appear on the home page. Drafts remain private.</p>
        <?php if ($articles === []): ?>
            <p class="mt-4 rounded-xl border border-white/10 p-5 text-sm text-white/60">No news articles have been created yet.</p>
        <?php else: ?>
            <ul class="mt-4 space-y-3">
                <?php foreach ($articles as $listedArticle): ?>
                    <li class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-white/10 bg-white/[0.03] p-4">
                        <div class="min-w-0">
                            <h3 class="font-semibold"><?= AdminPageController::escape($listedArticle["title"]) ?></h3>
                            <p class="mt-1 text-xs text-white/50">
                                <?= (int) $listedArticle["is_published"] === 1 ? "Published" : "Draft" ?>
                                · <?= AdminPageController::escape(date("d M Y", strtotime($listedArticle["created_at"]))) ?>
                            </p>
                        </div>
                        <div class="flex shrink-0 gap-2">
                            <a class="rounded-lg border border-white/15 px-4 py-2 text-sm font-semibold hover:bg-white/5" href="manage_news.php?id=<?= (int) $listedArticle["news_id"] ?>">Edit</a>
                            <form action="manage_news.php" method="post" onsubmit="return confirm('Delete this news article?');">
                                <input type="hidden" name="csrf_token" value="<?= AdminPageController::escape($csrfToken) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="news_id" value="<?= (int) $listedArticle["news_id"] ?>">
                                <button class="rounded-lg border border-red-400/40 px-4 py-2 text-sm font-semibold text-red-200 hover:bg-red-400/10" type="submit">Delete</button>
                            </form>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</main>

<?php include __DIR__ . "/footer.php"; ?>

<?php require_once __DIR__ . "/parts/session.php"; ?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mystery Cinema</title>
    <link href="./dist/output.css" rel="stylesheet">
</head>
<body class="m-0 min-h-screen min-w-[320px] bg-night font-lexend text-background">
    <header class="grid min-h-[68px] grid-cols-[1fr_auto_1fr] items-center gap-6 border-b border-white/10 px-[clamp(20px,4vw,56px)] py-2.5 max-[850px]:grid-cols-[1fr_auto] max-[480px]:gap-3 max-[480px]:px-4">
        <a class="inline-flex w-fit focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-background" href="./index.php" aria-label="Mystery Cinema - home">
            <img class="block h-auto w-[106px] max-[480px]:w-[88px]" src="./pictures/mclogo.png" alt="Mystery Cinema">
        </a>

        <nav class="flex flex-wrap items-center justify-center gap-x-[clamp(16px,2vw,28px)] gap-y-3 max-[850px]:col-span-2 max-[850px]:row-start-2 max-[850px]:py-2 max-[480px]:gap-x-[18px]" aria-label="Main navigation">
            <a class="whitespace-nowrap text-sm font-bold text-background transition-colors hover:text-background focus-visible:text-background focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-background" href="./index.php" aria-current="page">Home</a>
            <a class="whitespace-nowrap text-sm font-medium text-[#a6a6a6] transition-colors hover:text-background focus-visible:text-background focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-background max-[480px]:text-xs" href="./movies.php">Movies</a>
            <a class="whitespace-nowrap text-sm font-medium text-[#a6a6a6] transition-colors hover:text-background focus-visible:text-background focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-background max-[480px]:text-xs" href="./mystery-cinema.php">Mystery Cinema</a>
            <a class="whitespace-nowrap text-sm font-medium text-[#a6a6a6] transition-colors hover:text-background focus-visible:text-background focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-background max-[480px]:text-xs" href="./food-and-drinks.php">Food &amp; Drinks</a>
            <a class="whitespace-nowrap text-sm font-medium text-[#a6a6a6] transition-colors hover:text-background focus-visible:text-background focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-background max-[480px]:text-xs" href="./about.php">About</a>
        </nav>

        <div class="flex items-center justify-self-end gap-4 max-[480px]:gap-2.5">
            <?php if (logged_in()): ?>
                <a class="whitespace-nowrap text-sm font-medium text-[#a6a6a6] transition-colors hover:text-background focus-visible:text-background focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-background max-[480px]:text-xs" href="./logout.php">Logout</a>
            <?php else: ?>
                <a class="whitespace-nowrap text-sm font-medium text-[#a6a6a6] transition-colors hover:text-background focus-visible:text-background focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-background max-[480px]:text-xs" href="./login.php">Login</a>
            <?php endif; ?>
            <a class="whitespace-nowrap rounded-[10px] bg-accent px-5 py-2.5 text-sm font-bold text-white transition-colors hover:bg-accent-dark focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-background max-[480px]:px-3 max-[480px]:py-[9px] max-[480px]:text-xs" href="./book-tickets.php">Book Tickets</a>
        </div>
    </header>
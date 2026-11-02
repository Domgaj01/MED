<?php

require_once __DIR__ . "/AdminPageController.php";
require_once __DIR__ . "/ScreeningManager.php";

class ScreeningsAdminController extends AdminPageController
{
    public bool $isCreating;
    public ?int $selectedScreeningId;
    public ?array $screening = null;
    public array $movies = [];
    public array $halls = [];
    public array $ticketTypes = [];
    public array $prices = [];
    public array $screenings = [];

    private ScreeningManager $screeningManager;
    private ?array $originalScreening = null;

    public function __construct(mysqli $connection, array $query, array $post, string $requestMethod)
    {
        parent::__construct($connection);

        $this->screeningManager = new ScreeningManager($connection);
        $this->isCreating = ($query["create"] ?? "") === "1";
        $this->selectedScreeningId = $this->positiveInteger($query["id"] ?? null);
        if ($this->selectedScreeningId !== null) {
            $this->originalScreening = $this->screeningManager->findById($this->selectedScreeningId);
        }
        $this->movies = $this->screeningManager->getMovies();
        $this->halls = $this->screeningManager->getHalls();
        $this->ticketTypes = $this->screeningManager->getTicketTypes();

        if ($requestMethod === "POST") {
            $this->handlePost($post);
        }

        $this->setSuccessMessage($query);
        $this->loadSelectedScreening();
        $this->screenings = $this->screeningManager->getAll();
    }

    private function handlePost(array $post): void
    {
        if (!$this->verifyCsrfToken($post)) {
            return;
        }

        $action = isset($post["action"]) && is_string($post["action"])
            ? $post["action"]
            : "";
        $this->selectedScreeningId = $this->positiveInteger($post["screening_id"] ?? null);

        if ($action === "delete") {
            $this->deleteScreening();
            return;
        }

        if ($action !== "create" && $action !== "update") {
            http_response_code(400);
            $this->error = "Invalid screening action.";
            return;
        }

        $this->isCreating = $action === "create";
        $movieId = $this->positiveInteger($post["movie_id"] ?? null);
        $hallId = $this->positiveInteger($post["hall_id"] ?? null);
        $startInput = $this->postedString($post, "start_time");
        $screeningType = $this->postedString($post, "screening_type");
        $status = $this->postedString($post, "status");
        $this->prices = $this->getPostedPrices($post["prices"] ?? []);
        $this->screening = [
            "movie_id" => $movieId,
            "hall_id" => $hallId,
            "start_time" => $startInput,
            "start_time_input" => $startInput,
            "screening_type" => $screeningType,
            "status" => $status,
        ];

        $movie = $this->findOption($this->movies, "movie_id", $movieId);
        $hall = $this->findOption($this->halls, "hall_id", $hallId);
        $startTime = DateTimeImmutable::createFromFormat("!Y-m-d\TH:i", $startInput);
        $timeErrors = DateTimeImmutable::getLastErrors();
        $validStartTime = $startTime !== false
            && ($timeErrors === false || ($timeErrors["warning_count"] === 0 && $timeErrors["error_count"] === 0))
            && $startTime->format("Y-m-d\TH:i") === $startInput;

        if (
            !$this->isCreating
            && (
                $this->selectedScreeningId === null
                || $this->originalScreening === null
                || $this->selectedScreeningId !== (int) $this->originalScreening["screening_id"]
            )
        ) {
            $this->error = "Choose a valid screening to edit.";
        } elseif ($movie === null) {
            $this->error = "Choose a valid movie.";
        } elseif ($hall === null) {
            $this->error = "Choose a valid hall.";
        } elseif (
            (int) $movie["movie_is_available"] !== 1
            && (int) $movieId !== (int) ($this->originalScreening["movie_id"] ?? 0)
        ) {
            $this->error = "Choose a movie that is available to show.";
        } elseif (
            (int) $hall["is_active"] !== 1
            && (int) $hallId !== (int) ($this->originalScreening["hall_id"] ?? 0)
        ) {
            $this->error = "Choose an active hall.";
        } elseif (
            !$validStartTime
            || ($status === "scheduled" && $startTime <= new DateTimeImmutable())
        ) {
            $this->error = "Enter a valid start date and time; scheduled screenings must start in the future.";
        } elseif ($screeningType === "" || mb_strlen($screeningType, "UTF-8") > 20) {
            $this->error = "Screening type is required and must be 20 characters or fewer.";
        } elseif (!in_array($status, ["scheduled", "cancelled", "completed"], true)) {
            $this->error = "Choose a valid screening status.";
        } elseif ($this->ticketTypes === []) {
            $this->error = "Add ticket types before creating a screening.";
        } elseif (count($this->prices) !== count($this->ticketTypes)) {
            $this->error = "Enter a valid price for every ticket type.";
        } else {
            $startTimeSql = $startTime->format("Y-m-d H:i:s");
            $endTime = $startTime
                ->modify("+" . (int) $movie["movie_duration"] . " minutes")
                ->format("Y-m-d H:i:s");
            $this->screening["start_time"] = $startTimeSql;

            try {
                if (
                    $status === "scheduled"
                    && $this->screeningManager->hasHallConflict(
                        $hallId,
                        $startTimeSql,
                        $endTime,
                        $this->selectedScreeningId
                    )
                ) {
                    $this->error = "This hall already has a scheduled screening during that time.";
                    return;
                }

                $this->selectedScreeningId = $this->screeningManager->save(
                    [
                        "movie_id" => $movieId,
                        "hall_id" => $hallId,
                        "start_time" => $startTimeSql,
                        "screening_type" => $screeningType,
                        "status" => $status,
                    ],
                    $endTime,
                    $this->prices,
                    $this->isCreating ? null : $this->selectedScreeningId
                );
                $successParameter = $this->isCreating ? "created=1" : "saved=1";
                $this->redirectTo(
                    "manage_screenings.php?id=" . $this->selectedScreeningId . "&" . $successParameter
                );
            } catch (DomainException $exception) {
                $this->error = $exception->getMessage();
            } catch (mysqli_sql_exception $exception) {
                error_log("Screening save failed: " . $exception->getMessage());
                $this->error = "Could not save the screening. Check the server log for details.";
            }
        }
    }

    private function deleteScreening(): void
    {
        if ($this->selectedScreeningId === null) {
            $this->error = "Choose a valid screening to delete.";
            return;
        }

        try {
            if ($this->screeningManager->delete($this->selectedScreeningId)) {
                $this->redirectTo("manage_screenings.php?deleted=1");
            }

            $this->error = "That screening could not be found.";
        } catch (DomainException $exception) {
            $this->error = $exception->getMessage();
        } catch (mysqli_sql_exception $exception) {
            error_log("Screening deletion failed: " . $exception->getMessage());
            $this->error = "Could not delete the screening. Check the server log for details.";
        }
    }

    private function getPostedPrices(mixed $postedPrices): array
    {
        if (!is_array($postedPrices)) {
            return [];
        }

        $prices = [];
        foreach ($this->ticketTypes as $ticketType) {
            $ticketTypeId = (int) $ticketType["ticket_type_id"];
            $price = $postedPrices[$ticketTypeId] ?? null;
            if (!is_string($price) && !is_int($price) && !is_float($price)) {
                continue;
            }

            $price = trim((string) $price);
            if (preg_match('/^\d{1,6}(?:\.\d{1,2})?$/', $price) === 1) {
                $prices[$ticketTypeId] = (float) $price;
            }
        }

        return $prices;
    }

    private function findOption(array $options, string $idKey, ?int $id): ?array
    {
        if ($id === null) {
            return null;
        }

        foreach ($options as $option) {
            if ((int) $option[$idKey] === $id) {
                return $option;
            }
        }

        return null;
    }

    private function postedString(array $post, string $key): string
    {
        return isset($post[$key]) && is_string($post[$key]) ? trim($post[$key]) : "";
    }

    private function setSuccessMessage(array $query): void
    {
        if (isset($query["created"])) {
            $this->message = "Screening created.";
        } elseif (isset($query["saved"])) {
            $this->message = "Screening changes saved.";
        } elseif (isset($query["deleted"])) {
            $this->message = "Screening deleted.";
        }
    }

    private function loadSelectedScreening(): void
    {
        if ($this->screening === null && $this->selectedScreeningId !== null) {
            $this->screening = $this->originalScreening;
            if ($this->screening === null) {
                if ($this->error === "") {
                    $this->error = "That screening could not be found.";
                }
                return;
            }

            $startTime = new DateTimeImmutable($this->screening["start_time"]);
            $this->screening["start_time_input"] = $startTime->format("Y-m-d\TH:i");
            $this->prices = $this->screeningManager->getPrices($this->selectedScreeningId);
        }

        if ($this->isCreating && $this->screening === null) {
            $this->screening = [
                "movie_id" => "",
                "hall_id" => "",
                "start_time" => "",
                "start_time_input" => "",
                "screening_type" => "regular",
                "status" => "scheduled",
            ];
            foreach ($this->ticketTypes as $ticketType) {
                $this->prices[(int) $ticketType["ticket_type_id"]] = "";
            }
        }
    }
}

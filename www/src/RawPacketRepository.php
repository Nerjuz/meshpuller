<?php

namespace MeshCore;

require_once __DIR__ . '/autoload.php';

use MeshCore\Contracts\BotRepositoryInterface;
use MeshCore\Contracts\DatabaseConnectionInterface;
use MeshCore\Contracts\GeolocationServiceInterface;
use MeshCore\Contracts\NodeRepositoryInterface;
use MeshCore\Contracts\PollingQueueServiceInterface;
use MeshCore\Contracts\RawPacketRepositoryInterface;
use MeshCore\Contracts\SettingsRepositoryInterface;
use MeshCore\Contracts\TelemetryRepositoryInterface;
use MeshCore\Database\DatabaseManager;
use MeshCore\Database\SchemaManager;
use MeshCore\Repositories\ApiLogRepository;
use MeshCore\Repositories\BotRepository;
use MeshCore\Repositories\NodeRepository;
use MeshCore\Repositories\RawPacketStorage;
use MeshCore\Repositories\SettingsRepository;
use MeshCore\Repositories\TelemetryRepository;
use MeshCore\Services\GeolocationService;
use MeshCore\Services\PollingQueueService;
use PDO;

/**
 * Class RawPacketRepository
 *
 * SOLID Facade / Aggregate Root.
 * Apjungia specializuotas saugyklas ir servisus (Single Responsibility Principle)
 * ir suteikia 100% atgalinį suderinamumą visoms esamoms sistemos dalims.
 *
 * Pagal SOLID:
 * - S (SRP): Kiekviena sritis (DB, schemos, mazgai, telemetrija, nustatymai, eilė, raw) turi savo atskirą klasę.
 * - O (OCP): Naujos saugyklos ar funkcionalumai plečiami per interfeisus nekeičiant esamo kodo.
 * - L (LSP): Visos saugyklos įgyvendina aiškius kontraktus (Contracts\*Interface).
 * - I (ISP): Klientai gali priklausyti nuo siaurų interfeisų (NodeRepositoryInterface, TelemetryRepositoryInterface ir t.t.).
 * - D (DIP): Klasės priima priklausomybes (PDO, kontraktus) per konstruktorių (Dependency Injection).
 */
class RawPacketRepository implements 
    DatabaseConnectionInterface,
    RawPacketRepositoryInterface,
    NodeRepositoryInterface,
    TelemetryRepositoryInterface,
    SettingsRepositoryInterface,
    GeolocationServiceInterface,
    PollingQueueServiceInterface,
    BotRepositoryInterface
{
    public const DEFAULT_CONFIG = DatabaseManager::DEFAULT_CONFIG;
    public const DEFAULT_SYSTEM_SETTINGS = SettingsRepository::DEFAULT_SYSTEM_SETTINGS;

    private DatabaseManager $dbManager;
    private SchemaManager $schemaManager;
    private SettingsRepository $settingsRepo;
    private GeolocationService $geoService;
    private PollingQueueService $pollingQueueService;
    private NodeRepository $nodeRepo;
    private TelemetryRepository $telemetryRepo;
    private RawPacketStorage $rawPacketStorage;
    private BotRepository $botRepo;
    private ApiLogRepository $apiLogRepo;

    public function __construct(
        ?array $config = null,
        ?PDO $pdo = null,
        ?DatabaseManager $dbManager = null,
        ?SchemaManager $schemaManager = null,
        ?SettingsRepository $settingsRepo = null,
        ?GeolocationService $geoService = null,
        ?PollingQueueService $pollingQueueService = null,
        ?NodeRepository $nodeRepo = null,
        ?TelemetryRepository $telemetryRepo = null,
        ?RawPacketStorage $rawPacketStorage = null,
        ?BotRepository $botRepo = null,
        ?ApiLogRepository $apiLogRepo = null
    ) {
        $this->dbManager = $dbManager ?? new DatabaseManager($config, $pdo);
        $activePdo = $this->dbManager->getPdo();
        $tableName = $this->dbManager->getConfig()['table'] ?? 'raw_packets';

        $this->schemaManager = $schemaManager ?? new SchemaManager($activePdo, $tableName);
        $this->settingsRepo = $settingsRepo ?? new SettingsRepository($activePdo);
        $this->geoService = $geoService ?? new GeolocationService($this->settingsRepo, $activePdo);
        $this->pollingQueueService = $pollingQueueService ?? new PollingQueueService($activePdo, $this->settingsRepo, $this->geoService);
        $this->botRepo = $botRepo ?? new BotRepository($activePdo);
        $this->apiLogRepo = $apiLogRepo ?? new ApiLogRepository($activePdo);

        $this->nodeRepo = $nodeRepo ?? new NodeRepository(
            $activePdo,
            $this->settingsRepo,
            $this->pollingQueueService,
            $this->botRepo,
            $tableName
        );

        $this->telemetryRepo = $telemetryRepo ?? new TelemetryRepository(
            $activePdo,
            $this->nodeRepo,
            $this->settingsRepo,
            $this->geoService,
            $tableName
        );

        $this->rawPacketStorage = $rawPacketStorage ?? new RawPacketStorage(
            $activePdo,
            $tableName,
            $this->nodeRepo,
            $this->telemetryRepo
        );
    }

    // =========================================================================
    // 🔌 GETTERS PRIKLAUSOMYBIŲ INJEKCIJAI (SOLID DIP / ISP)
    // =========================================================================

    public function getDatabaseManager(): DatabaseManager
    {
        return $this->dbManager;
    }

    public function getSchemaManager(): SchemaManager
    {
        return $this->schemaManager;
    }

    public function getSettingsRepository(): SettingsRepository
    {
        return $this->settingsRepo;
    }

    public function getGeolocationService(): GeolocationService
    {
        return $this->geoService;
    }

    public function getPollingQueueService(): PollingQueueService
    {
        return $this->pollingQueueService;
    }

    public function getNodeRepository(): NodeRepository
    {
        return $this->nodeRepo;
    }

    public function getTelemetryRepository(): TelemetryRepository
    {
        return $this->telemetryRepo;
    }

    public function getRawPacketStorage(): RawPacketStorage
    {
        return $this->rawPacketStorage;
    }

    public function getBotRepository(): BotRepository
    {
        return $this->botRepo;
    }

    // =========================================================================
    // 🗄️ DATABASE CONNECTION INTERFACE
    // =========================================================================

    public function getPdo(): PDO
    {
        return $this->dbManager->getPdo();
    }

    public function getConfig(): array
    {
        return $this->dbManager->getConfig();
    }

    public function getDriver(): string
    {
        return $this->dbManager->getDriver();
    }

    // =========================================================================
    // 🧱 SCHEMA MANAGEMENT
    // =========================================================================

    public function createTableIfNotExists(): void
    {
        $this->schemaManager->createTableIfNotExists(function () {
            $this->cleanupInvalidTelemetry();
        });
    }

    // =========================================================================
    // 📦 RAW PACKETS REPOSITORY INTERFACE
    // =========================================================================

    public function saveRaw($data, ?string $sharedSecretHex = null): int
    {
        return $this->rawPacketStorage->saveRaw($data, $sharedSecretHex);
    }

    public function fetchNextForProcessing(): ?array
    {
        return $this->rawPacketStorage->fetchNextForProcessing();
    }

    public function completeProcessing(int $id, array $decodedData, ?string $errorMessage = null): bool
    {
        return $this->rawPacketStorage->completeProcessing($id, $decodedData, $errorMessage);
    }

    public function failProcessing(int $id, string $errorMessage, ?array $partialDecoded = null): bool
    {
        return $this->rawPacketStorage->failProcessing($id, $errorMessage, $partialDecoded);
    }

    public function processRecord(int $id, MeshCoreDecoder $decoder): array
    {
        return $this->rawPacketStorage->processRecord($id, $decoder);
    }

    public function processNext(MeshCoreDecoder $decoder): ?array
    {
        return $this->rawPacketStorage->processNext($decoder);
    }

    public function syncErrorsFromDecodedJson(int $limit = 500): int
    {
        return $this->rawPacketStorage->syncErrorsFromDecodedJson($limit);
    }

    public function updateDecodedJsonWithoutCompleting(int $id, array $data): void
    {
        $this->rawPacketStorage->updateDecodedJsonWithoutCompleting($id, $data);
    }

    public function getStats(): array
    {
        return $this->rawPacketStorage->getStats();
    }

    public function getFilteredRawPackets(
        array $filters = [],
        int $limit = 50,
        int $offset = 0,
        ?string $sortBy = null,
        ?string $sortDir = 'DESC'
    ): array {
        return $this->rawPacketStorage->getFilteredRawPackets($filters, $limit, $offset, $sortBy, $sortDir);
    }

    public function getRawPacketDetails(int $id): ?array
    {
        return $this->rawPacketStorage->getRawPacketDetails($id);
    }

    // =========================================================================
    // 📡 NODE REPOSITORY INTERFACE
    // =========================================================================

    public function upsertNodeFromAdvert(array $advert, ?array $packetMeta = null): bool
    {
        return $this->nodeRepo->upsertNodeFromAdvert($advert, $packetMeta);
    }

    public function touchNodeSeen(
        string $publicKeyOrNodeId,
        ?string $botId = null,
        ?float $batteryV = null,
        ?int $batteryMv = null,
        ?float $rssi = null,
        ?float $snr = null,
        bool $hasTelemetry = false,
        ?float $temperatureC = null,
        ?string $returnPath = null
    ): bool {
        return $this->nodeRepo->touchNodeSeen(
            $publicKeyOrNodeId,
            $botId,
            $batteryV,
            $batteryMv,
            $rssi,
            $snr,
            $hasTelemetry,
            $temperatureC,
            $returnPath
        );
    }

    public function getNodes(?string $type = null, int $limit = 100): array
    {
        return $this->nodeRepo->getNodes($type, $limit);
    }

    public function getNodeStats(): array
    {
        return $this->nodeRepo->getNodeStats();
    }

    public function backfillNodesFromRawPackets(MeshCoreDecoder $decoder): int
    {
        return $this->nodeRepo->backfillNodesFromRawPackets($decoder);
    }

    public function hasAnyNodes(): bool
    {
        return $this->nodeRepo->hasAnyNodes();
    }

    public function updateNodeDetails(string $publicKeyOrNodeId, array $fields): bool
    {
        return $this->nodeRepo->updateNodeDetails($publicKeyOrNodeId, $fields);
    }

    public function getFilteredNodes(
        array $filters = [],
        int $limit = 50,
        int $offset = 0,
        ?string $sortBy = null,
        ?string $sortDir = 'DESC'
    ): array {
        return $this->nodeRepo->getFilteredNodes($filters, $limit, $offset, $sortBy, $sortDir);
    }

    public function deleteNode(string $publicKeyOrNodeId): bool
    {
        return $this->nodeRepo->deleteNode($publicKeyOrNodeId);
    }

    public function cleanupInvalidSignatureNodes(bool $force = false): int
    {
        return $this->nodeRepo->cleanupInvalidSignatureNodes($force);
    }

    // =========================================================================
    // 🔋 TELEMETRY REPOSITORY INTERFACE
    // =========================================================================

    public function extractAndSaveTelemetry(int $rawPacketId, array $decoded, ?array $record = null): ?int
    {
        return $this->telemetryRepo->extractAndSaveTelemetry($rawPacketId, $decoded, $record);
    }

    public function insertTelemetryRecord(array $data): ?int
    {
        return $this->telemetryRepo->insertTelemetryRecord($data);
    }

    public function getTelemetryHistory(string $nodeId, ?string $from = null, ?string $to = null, int $limit = 500): array
    {
        return $this->telemetryRepo->getTelemetryHistory($nodeId, $from, $to, $limit);
    }

    public function getTelemetryStats(): array
    {
        return $this->telemetryRepo->getTelemetryStats();
    }

    public function backfillTelemetryFromRawPackets(MeshCoreDecoder $decoder): int
    {
        return $this->telemetryRepo->backfillTelemetryFromRawPackets($decoder);
    }

    public function getRepeatersWithLatestTelemetry(bool $repeatersOnly = true, int $limit = 500): array
    {
        return $this->telemetryRepo->getRepeatersWithLatestTelemetry($repeatersOnly, $limit);
    }

    public function cleanupInvalidTelemetry(): int
    {
        return $this->telemetryRepo->cleanupInvalidTelemetry();
    }

    public function getFilteredTelemetry(
        array $filters = [],
        int $limit = 50,
        int $offset = 0,
        ?string $sortBy = null,
        ?string $sortDir = 'DESC'
    ): array {
        return $this->telemetryRepo->getFilteredTelemetry($filters, $limit, $offset, $sortBy, $sortDir);
    }

    public function getNodeTelemetryHistory(string $nodeIdentifier, ?int $days = null): array
    {
        return $this->telemetryRepo->getNodeTelemetryHistory($nodeIdentifier, $days);
    }

    // =========================================================================
    // ⚙️ SETTINGS REPOSITORY INTERFACE
    // =========================================================================

    public function getAllSettings(): array
    {
        return $this->settingsRepo->getAllSettings();
    }

    public function getSetting(string $key, $default = null)
    {
        return $this->settingsRepo->getSetting($key, $default);
    }

    public function saveSettings(array $newSettings): array
    {
        return $this->settingsRepo->saveSettings($newSettings);
    }

    // =========================================================================
    // 🗺️ GEOLOCATION SERVICE INTERFACE
    // =========================================================================

    public function isPointInLithuania(?float $lat, ?float $lon, ?array $settings = null): bool
    {
        return $this->geoService->isPointInLithuania($lat, $lon, $settings);
    }

    public function filterNodesByGeolocationSettings(array $nodes): array
    {
        return $this->geoService->filterNodesByGeolocationSettings($nodes);
    }

    public function filterNodesByTelemetryAgeSettings(array $nodes, ?array $settings = null): array
    {
        return $this->geoService->filterNodesByTelemetryAgeSettings($nodes, $settings);
    }

    public function getGeolocationFilterStats(): array
    {
        return $this->geoService->getGeolocationFilterStats();
    }

    // =========================================================================
    // 🔄 POLLING QUEUE SERVICE INTERFACE
    // =========================================================================

    public function setNodeMonitoring(string $publicKeyOrNodeId, ?string $password = null, ?bool $update = null): bool
    {
        return $this->pollingQueueService->setNodeMonitoring($publicKeyOrNodeId, $password, $update);
    }

    public function getMonitoredRepeaters(?string $botId = null): array
    {
        return $this->pollingQueueService->getMonitoredRepeaters($botId);
    }

    public function syncFailedTelemetryAttempts(int $timeoutSeconds = 180): int
    {
        return $this->pollingQueueService->syncFailedTelemetryAttempts($timeoutSeconds);
    }

    public function getNextRepeaterToRequest(?string $botId = null): ?array
    {
        return $this->pollingQueueService->getNextRepeaterToRequest($botId);
    }

    public function getNodePollingQueue(?string $botId = null, bool $monitoredOnly = true, ?string $search = null): array
    {
        return $this->pollingQueueService->getNodePollingQueue($botId, $monitoredOnly, $search);
    }

    public function getAvailableBotSources(): array
    {
        return $this->pollingQueueService->getAvailableBotSources();
    }

    public function resetNodeRequestedDate(string $identifier, bool $resetFailCount = true): bool
    {
        return $this->pollingQueueService->resetNodeRequestedDate($identifier, $resetFailCount);
    }

    public function resetNodeFailCount(string $identifier): bool
    {
        return $this->pollingQueueService->resetNodeFailCount($identifier);
    }

    public function resetAllFailCounts(?string $botId = null): int
    {
        return $this->pollingQueueService->resetAllFailCounts($botId);
    }

    public function resetAllRequestedDates(?string $botId = null, bool $monitoredOnly = true, bool $resetFailCount = false): int
    {
        return $this->pollingQueueService->resetAllRequestedDates($botId, $monitoredOnly, $resetFailCount);
    }

    // =========================================================================
    // 🤖 BOT REPOSITORY INTERFACE
    // =========================================================================

    public function getAllBots(): array
    {
        return $this->botRepo->getAllBots();
    }

    public function getBotsWithNodeCounts(): array
    {
        return $this->botRepo->getBotsWithNodeCounts();
    }

    public function getBotById(string $botId): ?array
    {
        return $this->botRepo->getBotById($botId);
    }

    public function getBotByPublicKey(string $publicKey): ?array
    {
        return $this->botRepo->getBotByPublicKey($publicKey);
    }

    public function createBot(array $data): bool
    {
        return $this->botRepo->createBot($data);
    }

    public function updateBot(string $botId, array $data): bool
    {
        return $this->botRepo->updateBot($botId, $data);
    }

    public function deleteBot(string $botId): bool
    {
        return $this->botRepo->deleteBot($botId);
    }

    public function touchBotSeen(string $botId): bool
    {
        return $this->botRepo->touchBotSeen($botId);
    }

    public function isBotEnabled(string $botId): bool
    {
        return $this->botRepo->isBotEnabled($botId);
    }

    public function findClosestBot(float $latitude, float $longitude, bool $onlyEnabled = true): ?array
    {
        return $this->botRepo->findClosestBot($latitude, $longitude, $onlyEnabled);
    }

    // =========================================================================
    // 🌐 API LOG REPOSITORY
    // =========================================================================

    public function getApiLogRepository(): ApiLogRepository
    {
        return $this->apiLogRepo;
    }

    public function logApiRequest(array $data): void
    {
        $this->apiLogRepo->logRequest($data);
    }

    public function getFilteredApiLogs(
        array $filters = [],
        int $limit = 50,
        int $offset = 0,
        ?string $sortBy = 'id',
        ?string $sortDir = 'DESC'
    ): array {
        return $this->apiLogRepo->getFilteredLogs($filters, $limit, $offset, $sortBy, $sortDir);
    }

    public function getApiLogDetail(int $id): ?array
    {
        return $this->apiLogRepo->getLogDetail($id);
    }

    public function getApiLogStats(): array
    {
        return $this->apiLogRepo->getLogStats();
    }

    public function clearApiLogs(?string $mode = 'all'): int
    {
        return $this->apiLogRepo->clearLogs($mode);
    }
}

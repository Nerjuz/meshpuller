<?php

namespace MeshCore\Contracts;

interface BotRepositoryInterface
{
    /**
     * Grąžina visų botų sąrašą
     */
    public function getAllBots(): array;

    /**
     * Grąžina visų botų sąrašą su priskirtų mazgų skaičiais
     */
    public function getBotsWithNodeCounts(): array;

    /**
     * Suranda botą pagal bot_id (pvz. 'KLP')
     */
    public function getBotById(string $botId): ?array;

    /**
     * Suranda botą pagal viešąjį raktą
     */
    public function getBotByPublicKey(string $publicKey): ?array;

    /**
     * Sukuria naują botą
     */
    public function createBot(array $data): bool;

    /**
     * Atnaujina boto informaciją
     */
    public function updateBot(string $botId, array $data): bool;

    /**
     * Pašalina botą
     */
    public function deleteBot(string $botId): bool;

    /**
     * Atnaujina boto paskutinio aktyvumo laiką (last_seen_at)
     */
    public function touchBotSeen(string $botId): bool;

    /**
     * Patikrina ar botas yra aktyvus / įjungtas (enabled)
     */
    public function isBotEnabled(string $botId): bool;

    /**
     * Suranda botą, kurio GPS koordinatės yra arčiausiai nurodytų (pagal Haversine atstumą)
     * Jei botų su GPS koordinatėmis nėra, grąžina null
     */
    public function findClosestBot(float $latitude, float $longitude, bool $onlyEnabled = true): ?array;
}

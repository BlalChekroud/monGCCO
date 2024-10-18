<?php

namespace App\Service;

use App\Repository\CampaignStatusRepository;

class CampaignStatusService
{
    private $campaignStatusRepository;

    public function __construct(CampaignStatusRepository $campaignStatusRepository)
    {
        $this->campaignStatusRepository = $campaignStatusRepository;
    }

    /**
     * Récupère les statuts de campagne indexés par ordre.
     */
    public function getCampaignStatuses(): array
    {
        $campaignStatuses = $this->campaignStatusRepository->findBy([], ['createdAt' => 'ASC']);

        if (count($campaignStatuses) < 7) {
            throw new \LogicException("Tous les statuts de campagne ne sont pas définis dans la base de données.");
        }

        $statusFinished = $campaignStatuses[2];
        $statusClosed = $campaignStatuses[3];
        $statusSuspended = $campaignStatuses[5];
        $statusCancelled = $campaignStatuses[6];

        $statusIndexMap = [];
        foreach ($campaignStatuses as $index => $status) {
            $statusIndexMap[$status->getId()] = $index;
        }

        return [
            'campaignStatuses' => $campaignStatuses,
            'statusFinished' => $statusFinished,
            'statusClosed' => $statusClosed,
            'statusSuspended' => $statusSuspended,
            'statusCancelled' => $statusCancelled,
            'statusIndexMap' => $statusIndexMap,
        ];
    }
}

<?php

namespace App\EventSubscriber;

use App\Repository\LogoRepository;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Twig\Environment;

class LogoSubscriber implements EventSubscriberInterface
{
    private $twig;
    private $logoRepository;

    public function __construct(Environment $twig, LogoRepository $logoRepository)
    {
        $this->twig = $twig;
        $this->logoRepository = $logoRepository;
    }

    public function onKernelController(ControllerEvent $event)
    {
        $logo = $this->logoRepository->findOneBy([]); // Récupère le premier logo
        $this->twig->addGlobal('logo', $logo); // Injecte la variable globale
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'kernel.controller' => 'onKernelController',
        ];
    }
}
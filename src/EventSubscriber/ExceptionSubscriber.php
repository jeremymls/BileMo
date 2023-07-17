<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ExceptionSubscriber implements EventSubscriberInterface
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        if ($exception instanceof HttpException) {
            $data['status'] = $exception->getStatusCode();
            $params = $event->getRequest()->attributes->get('_route_params');
            if ($data['status'] == 404) {
                if (array_key_exists('ref', $params)) {
                    $data['message'] = "La référence " . $params['ref'] . " n'existe pas.";
                } elseif (array_key_exists('client', $params)) {
                    $data['message'] = "Le client #" . $params['client'] . " n'existe pas.";
                } elseif (array_key_exists('id', $params)) {
                    $data['message'] = "L'utilisateur #" . $params['id'] . " n'existe pas.";
                } else {
                    $data['message'] = $exception->getMessage();
                }
            } else {
                $data['message'] = $exception->getMessage();
            }

            $event->setResponse(new JsonResponse($data));
        } else {
            $data = [
                'status' => 500,
                'message' => $exception->getMessage(),
            ];

            $event->setResponse(new JsonResponse($data));
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'kernel.exception' => 'onKernelException',
        ];
    }
}

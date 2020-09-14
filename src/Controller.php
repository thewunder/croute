<?php
namespace Croute;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Action methods:
 * Must end in "Action"
 * Must be public
 * May have one or more parameters that will be populated automatically from the request based on the parameter name
 *
 */
abstract class Controller implements ControllerInterface
{
    /** @var Request */
    protected $request;

    /**
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * @param Request $request
     */
    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }

    /**
     * @param string $url
     * @param int $status
     * @return RedirectResponse
     */
    protected function redirect(string $url, int $status = 302): RedirectResponse
    {
        return new RedirectResponse($url, $status);
    }

    /**
     * @param string|\SplFileInfo $file
     * @param string $disposition attachment or inline
     * @return BinaryFileResponse
     */
    protected function fileDownload($file, string $disposition = ResponseHeaderBag::DISPOSITION_ATTACHMENT): BinaryFileResponse
    {
        $response = new BinaryFileResponse($file);
        $response->setContentDisposition($disposition);
        return $response;
    }

    /**
     * @param mixed $data
     * @param int $status
     * @return JsonResponse
     */
    protected function json($data, int $status = 200): JsonResponse
    {
        return new JsonResponse($data, $status);
    }

    /**
     * @param string $text
     * @return Response
     */
    protected function notFound(string $text = 'Not Found'): Response
    {
        return new Response($text, 404);
    }
}

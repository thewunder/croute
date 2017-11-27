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
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param Request $request
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @param $url
     * @return Response
     */
    protected function redirect($url)
    {
        return RedirectResponse::create($url);
    }

    /**
     * @param string|\SplFileInfo $file
     * @param string $disposition attachment or inline
     * @return BinaryFileResponse
     */
    protected function fileDownload($file, $disposition = ResponseHeaderBag::DISPOSITION_ATTACHMENT)
    {
        $response = new BinaryFileResponse($file);
        $response->setContentDisposition($disposition);
        return $response;
    }

    /**
     * @param $data
     * @param int $status
     * @return JsonResponse
     */
    protected function json($data, $status = 200)
    {
        return new JsonResponse($data, $status);
    }

    /**
     * @param $text
     * @return Response
     */
    protected function notFound($text = 'Not Found')
    {
        return new Response($text, 404);
    }
}

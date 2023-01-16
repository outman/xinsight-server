<?php

namespace App\Application\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Log\LoggerInterface;
use Slim\Psr7\Response as IResponse;

class AuthMiddleware implements MiddlewareInterface
{
    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Request $request, RequestHandler $handler): Response
    {
        try {
            $tokens = $request->getHeader('XINSIGHT-TOKEN');
            if (empty($tokens) || empty($tokens[0])) {
                return $this->withDenyResponse();
            }

            $token = $tokens[0];
            $payload = JWT::decode($token, new Key($_ENV['JWT_KEY'], $_ENV['JWT_ALG']));
            if (!property_exists($payload, 'name')) {
                return $this->withDenyResponse();
            }

            $users = str_split_to_options($_ENV['SYSTEM_USERS'] ?? '');
            if (
                isset($users[$payload->name])
                && $payload->iss == $_ENV['JWT_DOMAIN']
                && $payload->sub == $_ENV['JWT_DOMAIN']
                && $payload->aud == $_ENV['JWT_DOMAIN']
                && $payload->exp > time()
            ) {
                return $handler->handle($request);
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }
        return $this->withDenyResponse();
    }

    /**
     * @return IResponse
     */
    protected function withDenyResponse(): IResponse
    {
        $response = new IResponse();
        $response = $response->withStatus(403);
        $response->getBody()->write(json_encode([
            'data' => 'deny',
        ]));
        return $response;
    }
}

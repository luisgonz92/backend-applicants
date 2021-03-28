<?php

namespace Osana\Challenge\Http\Controllers;

use Osana\Challenge\Domain\Users\Login;
use Osana\Challenge\Domain\Users\User;
use Osana\Challenge\Services\GitHub\GitHubUsersRepository;
use Osana\Challenge\Services\Local\LocalUsersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class FindUsersController
{
    /** @var LocalUsersRepository */
    private $localUsersRepository;

    /** @var GitHubUsersRepository */
    private $gitHubUsersRepository;

    public function __construct(LocalUsersRepository $localUsersRepository, GitHubUsersRepository $gitHubUsersRepository)
    {
        $this->localUsersRepository = $localUsersRepository;
        $this->gitHubUsersRepository = $gitHubUsersRepository;
    }

    public function format_and_load_file_(){
        $profiles = fopen("C:\Users\Sincropool\Documents\prueba\backend-applicants-develop\data\profiles.csv", "r");
        $users = fopen("C:\Users\Sincropool\Documents\prueba\backend-applicants-develop\data\users.csv", "r");
        $prueba=1;
        foreach ($profiles as $profile) {
            foreach ($users as $user) {
                if ($user->id === $profile->id) {
                    return [
                        'id' => $user->id,
                        'login' => $user->login,
                        'type' => $user->type,
                        'profile' => [
                            'name' => $profile->name,
                            'company' => $profile->compay,
                            'location' => $profile->location,
                        ]
                    ];
                }
            }
        }

    }

    public api_connect(){
        $URL	= 'https://api.github.com/users/osana-salud';
        $rs 	= API::Authentication($URL.'authentication','usuario','clave');
        $array  = API::JSON_TO_ARRAY($rs);
        $token 	= $array['data']['APIKEY'];
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $query = $request->getQueryParams()['q'] ?? '';
        $limit = $request->getQueryParams()['limit'] ?? 0;

        $login = new Login($query);

        // FIXME: Se debe tener cuidado en la implementación
        // para que siga las notas del documento de requisitos
        $localUsers = $this->localUsersRepository->findByLogin($login, $limit);
        $githubUsers = $this->gitHubUsersRepository->findByLogin($login, $limit);

        $users = $localUsers->merge($githubUsers)->map(function (User $user) {
            return [
                'id' => $user->getId()->getValue(),
                'login' => $user->getLogin()->getValue(),
                'type' => $user->getType()->getValue(),
                'profile' => [
                    'name' => $user->getProfile()->getName()->getValue(),
                    'company' => $user->getProfile()->getCompany()->getValue(),
                    'location' => $user->getProfile()->getLocation()->getValue(),
                ]
            ];
        });

        $response->getBody()->write($users->toJson());

        return $response->withHeader('Content-Type', 'application/json')
            ->withStatus(200, 'OK');
    }
}

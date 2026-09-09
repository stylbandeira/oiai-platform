<?php

namespace App\Actions\Unity;

use App\Repositories\UnityRepository;

class IndexUnityAction
{
    public function __construct(
        private UnityRepository $unity_repository,
    ) {}

    public function execute(array $array)
    {
        $unities = $this->unity_repository->paginate($array);
        return $unities;
    }
}

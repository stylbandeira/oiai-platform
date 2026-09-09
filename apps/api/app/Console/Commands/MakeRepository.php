<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeRepository extends Command
{
    protected $signature = 'make:repository {name} {--model=}';
    protected $description = 'Create a new repository class';

    public function handle()
    {
        $name = $this->argument('name');
        $model = $this->option('model') ?: $this->guessModelName($name);

        // Garantir que o diretório existe
        $directory = app_path('Repositories');
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $path = $directory . '/' . $name . '.php';

        if (file_exists($path)) {
            $this->error("Repository {$name} already exists!");
            return 1;
        }

        // Caminho do stub
        $stubPath = __DIR__ . '/stubs/repository.stub';

        if (!file_exists($stubPath)) {
            $this->error("Stub file not found at: {$stubPath}");
            $this->info("Creating default stub...");

            // Criar stub automaticamente se não existir
            $this->createDefaultStub($stubPath);
        }

        // Ler e processar o stub
        $stub = file_get_contents($stubPath);
        $stub = str_replace('{{ClassName}}', $name, $stub);
        $stub = str_replace('{{Model}}', $model, $stub);

        // Escrever o arquivo
        file_put_contents($path, $stub);

        $this->info("Repository {$name} created successfully at: {$path}");
        $this->info("Model namespace: App\\Models\\{$model}");

        return 0;
    }

    /**
     * Tentar adivinhar o nome do modelo baseado no nome do repositório
     */
    protected function guessModelName($repositoryName)
    {
        // Remover "Repository" do final se existir
        $modelName = preg_replace('/Repository$/', '', $repositoryName);

        // Remover "Repo" do final se existir
        $modelName = preg_replace('/Repo$/', '', $modelName);

        // Se ainda estiver vazio, usar padrão
        if (empty($modelName)) {
            return 'Model';
        }

        return $modelName;
    }

    /**
     * Criar um stub padrão se não existir
     */
    protected function createDefaultStub($stubPath)
    {
        $stubContent = '<?php

namespace App\Repositories;

use App\Models\{{Model}};

class {{ClassName}}
{
    protected $model;

    public function __construct({{Model}} $model)
    {
        $this->model = $model;
    }

    public function all()
    {
        return $this->model->all();
    }

    public function find($id)
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update($id, array $data)
    {
        $record = $this->find($id);
        $record->update($data);
        return $record;
    }

    public function delete($id)
    {
        return $this->model->destroy($id);
    }
}';

        // Criar diretório de stubs se não existir
        $stubDir = dirname($stubPath);
        if (!File::exists($stubDir)) {
            File::makeDirectory($stubDir, 0755, true);
        }

        file_put_contents($stubPath, $stubContent);
    }
}

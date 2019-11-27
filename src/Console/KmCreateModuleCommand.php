<?php


namespace KM\KMCrud\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;

class KmCreateModuleCommand extends GeneratorCommand
{
    protected $signature = 'km_make:module {name : The name of the class}';
    protected $name = 'km_make:module';
    protected $type = 'km_module';

    protected $description = 'Simple KM Module';
    protected function buildClass($name)
    {
        $stub = $this->files->get($this->getStub());

        return $this->replaceNamespace($stub, $name)
            ->replaceModelLowerCasePlural($stub, $name)
            ->replaceModelLowerCase($stub, $name)
            ->replaceModel($stub, $name)
            ->replaceClass($stub, $name);
    }

    /**
     * Replace the model key-word on stub.
     * DummyNamespace
     * DummyModel
     * DummyClass
     * @param  string  $stub
     * @param  string  $name
     * @return $this
     */
    protected function replaceModel(&$stub, $name)
    {
        $class_name = $this->getModelName($name);
        $stub = str_replace('DummyModel', $class_name, $stub);

        return $this;
    }

    protected function replaceModelLowerCase(&$stub, $name) {
        $class_name = strtolower($this->getModelName($name));

        $stub = str_replace('DummyModelLowerCase', $class_name, $stub);

        return $this;
    }

    protected function replaceModelLowerCasePlural(&$stub, $name) {
        $class_name = str_plural(strtolower($this->getModelName($name)));

        $stub = str_replace('DummyModelPluralLowerCasePlural', $class_name, $stub);

        return $this;
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Module';
    }

    protected function getPath($name)
    {
        $name = Str::replaceFirst($this->rootNamespace(), '', $name);

        return base_path('KM').str_replace('\\', '/', $name).'.php';
    }

    protected function getStub()
    {
        return __DIR__.'/Stubs/module-config.stub';
    }

    protected function rootNamespace()
    {
        return 'KM';
    }

    private function getModelName($name){
        return substr(str_replace($this->getNamespace($name).'\\', '', $name), 0, -4);
    }
}

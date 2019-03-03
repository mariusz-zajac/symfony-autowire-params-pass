<?php

namespace AppBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Inspects existing service definitions and wires the parameters of autowired ones.
 */
class AutowireParamsPass implements CompilerPassInterface
{

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        foreach ($container->getDefinitions() as $alias => $definition) {
            if (!$this->isValidDefinition($definition)) {
                continue;
            }

            $reflection = $container->getReflectionClass($definition->getClass(), false);

            if (!$reflection) {
                $container->log($this, sprintf('Skipping service "%s": Class or interface "%s" cannot be loaded.', $alias, $definition->getClass()));
                continue;
            }

            $constructor = $reflection->getConstructor();

            if ($constructor) {
                $params = $constructor->getParameters();
                $doc = (string) $constructor->getDocComment();

                foreach ($params as $param) {
                    $paramName = '$' . $param->getName();

                    // if the service has already defined argument, don't replace it
                    if ($this->hasArgument($definition, $paramName)) {
                        continue;
                    }

                    $injection = $this->getInjection($doc, $paramName);

                    if (null !== $injection) {
                        $definition->setArgument($paramName, $this->prepareArgument($injection));
                    }
                }
            }
        }
    }

    /**
     * Is the service definition valid?
     *
     * @param mixed $definition Service definition
     *
     * @return bool
     */
    private function isValidDefinition($definition): bool
    {
        return $definition instanceof Definition
            && $definition->isAutowired()
            && !$definition->isAbstract()
            && $definition->getClass();
    }

    /**
     * Check if the service definition has a given argument
     *
     * @param Definition $definition Service definition
     * @param string     $argument   Service argument
     *
     * @return bool
     */
    private function hasArgument(Definition $definition, string $argument): bool
    {
        return array_key_exists($argument, $definition->getArguments());
    }

    /**
     * Get injected argument from doc comment
     *
     * @param string $doc       Doc comment
     * @param string $paramName Parameter name
     *
     * @return string|null
     */
    private function getInjection(string $doc, string $paramName): ?string
    {
        $matches = [];
        $pattern = '/' . preg_quote($paramName, '/') . '\s*@inject\((.+)\)/';

        if (preg_match($pattern, $doc, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Prepare argument to inject to the service
     *
     * @param string $argument An argument
     *
     * @return Reference|string
     */
    private function prepareArgument(string $argument)
    {
        if (preg_match('/^%.+%$/', $argument)) {
            return $argument;
        }

        return new Reference($argument);
    }
}

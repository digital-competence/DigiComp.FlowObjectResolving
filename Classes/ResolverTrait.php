<?php

namespace DigiComp\FlowObjectResolving;

use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\Reflection\ReflectionService;

trait ResolverTrait
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var PackageManager
     */
    protected $packageManager;

    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function injectObjectManager(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param PackageManager $packageManager
     */
    public function injectPackageManager(PackageManager $packageManager)
    {
        $this->packageManager = $packageManager;
    }

    /**
     * @return array
     */
    public function getAvailableNames(): array
    {
        $classNames = static::getImplementationClassNames($this->objectManager);
        $names = [];

        foreach (\array_keys($classNames) as $className) {
            $names[] = $this->inferTypeFromClassName($className);
        }
        return $names;
    }

    /**
     * @param string $className
     * @return string
     */
    protected function inferTypeFromClassName(string $className): string
    {
        $packageKey = null;
        $remaining = null;
        foreach ($this->packageManager->getAvailablePackages() as $package)
        {
            $autoloadConfiguration = $package->getFlattenedAutoloadConfiguration();
            if (! empty($autoloadConfiguration)) {
                if (strpos($className, $autoloadConfiguration[0]['namespace']) === 0) {
                    $packageKey = $package->getPackageKey();
                    $remaining = substr($className, strlen($autoloadConfiguration[0]['namespace']));
                }
            }
        }

        if (static::appendInterfaceName()) {
            if (substr($remaining, -strlen(static::getClassNameAppendix())) === static::getClassNameAppendix()) {
                $remaining = substr($remaining, 0, -strlen(static::getClassNameAppendix()));
            } else {
                return $className;
            }
        }

        $managedNamespace = static::getManagedNamespace($packageKey);
        if ($managedNamespace && strpos($remaining, $managedNamespace) === 0) {
            $remaining = substr($remaining, strlen($managedNamespace));
        } else {
            return $className;
        }
        if ($packageKey === static::getDefaultPackageKey($this->objectManager)) {
            return $remaining;
        }
        return $packageKey . ':' . $remaining;
    }

    /**
     * @param string $type
     * @param array $options
     * @return object
     * @throws Exception
     */
    public function create(string $type, array $options = []): object
    {
        $objectName = $this->resolveObjectName($type);
        if (! class_exists($objectName)) {
            throw new Exception(
                'Type ' . $type . ' resolved to ' . $objectName . ' but this class does not exist',
                1603541091
            );
        }
        #TODO: What about objects, which do not have options? switchable behavior, or another resolver?
        return new $objectName($options);
    }

    /**
     * Returns all class names implementing the Interface.
     *
     * @Neos\Flow\Annotations\CompileStatic
     *
     * @param ObjectManagerInterface $objectManager
     * @return array Array of class names implementing Interface indexed by class name
     */
    protected static function getImplementationClassNames(ObjectManagerInterface $objectManager): array
    {
        $reflectionService = $objectManager->get(ReflectionService::class);
                $classNames = $reflectionService->getAllImplementationClassNamesForInterface(static::getManagedInterface());
        return \array_flip($classNames);
    }

    /**
     * @Neos\Flow\Annotations\CompileStatic
     *
     * @param ObjectManagerInterface $objectManager
     * @return string
     */
    protected static function getDefaultPackageKey(ObjectManagerInterface $objectManager): string
    {
        $packageManager = $objectManager->get(PackageManager::class);
        $packageKey = 'UNKNOWN';
        foreach ($packageManager->getAvailablePackages() as $package)
        {
            $autoloadConfiguration = $package->getFlattenedAutoloadConfiguration();
            if (! empty($autoloadConfiguration)) {
                if (strpos(__CLASS__, $autoloadConfiguration[0]['namespace']) === 0) {
                    $packageKey = $package->getPackageKey();
                }
            }
        }

        return $packageKey;
    }


    /**
     * @param string $type
     * @return string
     */
    protected function resolveObjectName(string $type): string
    {
        $type = \ltrim($type, '\\');

        $classNames = static::getImplementationClassNames($this->objectManager);

        if ($this->objectManager->isRegistered($type) && isset($classNames[$type])) {
            //maybe we should check for instance of here...
            return $type;
        }

        if (\strpos($type, ':') === false) {
            $packageName = static::getDefaultPackageKey($this->objectManager);
            $objectName = $type;
        } else {
            list ($packageName, $objectName) = \explode(':', $type, 2);
        }
        $namespace = static::getManagedNamespace($packageName);

        $packageKeyPath = $this->packageManager->getPackage($packageName)->getNamespaces()[0];

        $possibleClassName = \sprintf(
            '%s%s%s',
            $packageKeyPath,
            $namespace,
            $objectName
        );

        if (static::appendInterfaceName()) {
            $possibleClassName .= static::getClassNameAppendix();
        }

        return $possibleClassName;
    }

    /**
     * @return string
     */
    abstract protected static function getManagedInterface(): string;

    protected static function getClassNameAppendix(): string
    {
        if (static::appendInterfaceName()) {
            \preg_match('~.*?([^\\\\]+)Interface~', static::getManagedInterface(), $matches);
            return $matches[1];
        }
        return '';
    }

    /**
     * The managed namespace is used between type name and package name
     *
     * @param string $packageName
     * @return string
     */
    abstract protected function getManagedNamespace(string $packageName = ''): string;

    /**
     * Should the prefix of the interface been appended to classNames or not
     *
     * @return bool
     */
    protected static function appendInterfaceName(): bool
    {
        return false;
    }
}

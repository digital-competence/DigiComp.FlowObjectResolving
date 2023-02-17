<?php

namespace DigiComp\FlowObjectResolving;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Package\Exception\UnknownPackageException;
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
    public function injectObjectManager(ObjectManagerInterface $objectManager): void
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param PackageManager $packageManager
     */
    public function injectPackageManager(PackageManager $packageManager): void
    {
        $this->packageManager = $packageManager;
    }

    /**
     * @return array
     */
    public function getAvailableNames(): array
    {
        $names = [];

        foreach (\array_keys(static::getImplementationClassNames($this->objectManager)) as $className) {
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

        foreach ($this->packageManager->getAvailablePackages() as $package) {
            $autoloadConfiguration = $package->getFlattenedAutoloadConfiguration();
            if ($autoloadConfiguration !== []) {
                if (\strpos($className, $autoloadConfiguration[0]['namespace']) === 0) {
                    $packageKey = $package->getPackageKey();
                    $remaining = \substr($className, \strlen($autoloadConfiguration[0]['namespace']));
                }
            }
        }

        if (static::appendInterfaceName()) {
            $classNameAppendix = static::getClassNameAppendix();
            if (\substr($remaining, -\strlen($classNameAppendix)) === $classNameAppendix) {
                $remaining = \substr($remaining, 0, -\strlen($classNameAppendix));
            } else {
                return $className;
            }
        }

        $managedNamespace = static::getManagedNamespace($packageKey);
        if ($managedNamespace !== '' && \strpos($remaining, $managedNamespace) === 0) {
            $remaining = \substr($remaining, \strlen($managedNamespace));
        } else {
            return $className;
        }

        if ($packageKey === static::getDefaultPackageKey($this->objectManager)) {
            return $remaining;
        }

        return $packageKey . ':' . $remaining;
    }

    /**
     * Returns all class names implementing the interface.
     *
     * @Flow\CompileStatic
     * @param ObjectManagerInterface $objectManager
     * @return array Array of class names implementing the interface indexed by class name.
     */
    protected static function getImplementationClassNames(ObjectManagerInterface $objectManager): array
    {
        return \array_flip(
            $objectManager
                ->get(ReflectionService::class)
                ->getAllImplementationClassNamesForInterface(static::getManagedInterface())
        );
    }

    /**
     * @Flow\CompileStatic
     * @param ObjectManagerInterface $objectManager
     * @return string
     */
    protected static function getDefaultPackageKey(ObjectManagerInterface $objectManager): string
    {
        $packageKey = 'UNKNOWN';

        foreach ($objectManager->get(PackageManager::class)->getAvailablePackages() as $package) {
            $autoloadConfiguration = $package->getFlattenedAutoloadConfiguration();
            if ($autoloadConfiguration !== []) {
                if (\strpos(__CLASS__, $autoloadConfiguration[0]['namespace']) === 0) {
                    $packageKey = $package->getPackageKey();
                }
            }
        }

        return $packageKey;
    }

    /**
     * @param string $type
     * @return string
     * @throws Exception
     * @throws UnknownPackageException
     */
    public function resolveObjectName(string $type): string
    {
        $type = \ltrim($type, '\\');

        if (
            $this->objectManager->isRegistered($type)
            && isset(static::getImplementationClassNames($this->objectManager)[$type])
        ) {
            // maybe we should check for instance of here...
            return $type;
        }

        if (\strpos($type, ':') === false) {
            $packageKey = static::getDefaultPackageKey($this->objectManager);
            $objectName = $type;
        } else {
            [$packageKey, $objectName] = \explode(':', $type, 2);
        }

        $possibleClassName =
            $this->packageManager->getPackage($packageKey)->getNamespaces()[0]
            . static::getManagedNamespace($packageKey)
            . $objectName
        ;

        if (static::appendInterfaceName()) {
            $possibleClassName .= static::getClassNameAppendix();
        }

        if (!\class_exists($possibleClassName)) {
            throw new Exception(
                'Type ' . $type . ' resolved to ' . $possibleClassName . ', but this class does not exist.',
                1603541091
            );
        }

        return $possibleClassName;
    }

    /**
     * @return string
     */
    abstract protected static function getManagedInterface(): string;

    /**
     * @return string
     */
    protected static function getClassNameAppendix(): string
    {
        if (static::appendInterfaceName()) {
            \preg_match('~.*?([^\\\\]+)Interface~', static::getManagedInterface(), $matches);

            return $matches[1];
        }

        return '';
    }

    /**
     * The managed namespace is used between type name and package name.
     *
     * @param string $packageKey
     * @return string
     */
    abstract protected static function getManagedNamespace(string $packageKey): string;

    /**
     * Whether the interface prefix should be appended to class names or not.
     *
     * @return bool
     */
    protected static function appendInterfaceName(): bool
    {
        return false;
    }
}

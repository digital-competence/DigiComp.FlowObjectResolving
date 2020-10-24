DigiComp.FlowObjectResolving
----------------------------


This package is here to help your DI to resolve nice human readable names to class names, to the class names which, 
should be used.

Additionaly it allows you to get a list of all those nice names, so you could list them from a CommandController, if 
you want to.

```php 
class PluginResolver
{
    use ResolverTrait;

    protected static function getManagedInterface(): string
    {
        return PluginInterface::class;
    }

    protected static function getManagedNamespace(): string
    {
        return 'FlysystemPlugin\\';
    }

    protected static function getDefaultPackageKey(ObjectManagerInterface $objectManager): string
    {
        return 'league.flysystem';
    }

    protected static function getDefaultNamespace(): string
    {
        return 'Plugin\\';
    }
}

```  

This class would for example list the following with a simple `getAvailableNames`:

```
./flow flysystem:listplugins
EmptyDir
ForcedCopy
ForcedRename
GetWithMetadata
ListFiles
ListPaths
ListWith
```

And those plugins can now be resolved to instances with `PluginResolver::create('EmptyDir')` for example.
`getDefaultNamespace` and `getDefaultPackage` are optional for your resolver and will be resolved to the class,
which used the trait, if not overriden.

The `ObjectManagerInterface` in `getDefaultPackageKey` is there, because this function makes use of the `CompileStatic` feature of flow.

The name is build in the scheme: `{packageKey}{nameSpace}{name}` - if `{packageKey}` is the default package key, the 
default namespace is used for `{namespace}`, otherwise the mandatory `mangedNamespace` is used. If you do not override
`getDefaultNamesapce` namespace is assumed to be the same as the managed one.

Classes which do not follow the default pattern can still be used, and will be listed, with the FQCN.

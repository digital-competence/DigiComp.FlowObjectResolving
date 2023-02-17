DigiComp.FlowObjectResolving
----------------------------

![Build status](https://ci.digital-competence.de/api/badges/Packages/DigiComp.FlowObjectResolving/status.svg)

This package is here to help your DI to resolve nice human readable names to class names, to the class names which, 
should be used.

Additionally, it allows you to get a list of all those nice names, so you could list them from a CommandController, if 
you want to.

```php
class DefaultResolver
{
    use ResolverTrait;

    protected static function getManagedInterface(): string
    {
        return RequestPatternInterface::class;
    }

    protected static function getManagedNamespace(string $packageKey): string
    {
        return 'Security\\RequestPattern\\';
    }
}
```  

This class would for example list the following with a simple `getAvailableNames`:

```php
['Neos.Flow:ControllerObjectName', 'Neos.Flow:CsrfProtection', 'Neos.Flow:Host',
 'Neos.Flow:Ip', 'Neos.Flow:Uri']
```

And those plugins can now be resolved to instances with `(new DefaultResolver)->resolveObjectName('Neos.Flow:ControllerObjectName')` for example.
`getDefaultNamespace` and `getDefaultPackage` are optional for your resolver and will be resolved to the class,
which used the trait, if not overridden.

The `ObjectManagerInterface` in `getDefaultPackageKey` is there, because this function makes use of the `CompileStatic`
feature of flow.

The name is build in the scheme: `{packageKey}{namespace}{name}` - if you need to have different namespaces for
different packages, you should implement your conditions, or even use configuration in `getManagedNamespace` - you'll get
the packageKey as argument.

Classes which do not follow the default pattern can still be used, and will be listed, with the FQCN.

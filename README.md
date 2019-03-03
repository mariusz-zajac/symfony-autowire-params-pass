# symfony3-autowire-params-pass
Symfony 3.3 autowire parameters pass

```php
class MyService
{

    /**
     * @var string
     */
    private $param;

    /**
     * @var SomeServiceInterface
     */
    private $service;

    /**
     * Constructor
     *
     * @param string               $param   @inject(%app.param%)
     * @param SomeServiceInterface $service @inject(AppBundle\Service\SomeService)
     */
    public function __construct(string $param, SomeServiceInterface $service)
    {
        $this->param = $param;
        $this->service = $service;
    }
    
    // ...
}
```

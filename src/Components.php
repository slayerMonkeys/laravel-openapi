<?php

namespace BIWEB\LaravelOpenapi;

use BIWEB\LaravelOpenapi\Exceptions\InvalidOpenapiDefinition;
use BIWEB\LaravelOpenapi\OpenApi\Objects;
use BIWEB\LaravelOpenapi\OpenApi\Objects\BaseObject;

/** The components of one generation: only the classes a resolved tree references are registered, each once. */
class Components
{
    /** @var array<class-string, string> component name of each registered class */
    private array $names = [];

    /** @var array<string, Objects\Schema> keyed by component name */
    private array $schemas = [];

    public function __construct(private readonly SchemaFactory $factory = new SchemaFactory) {}

    /**
     * Replaces the class-string references of the tree by component pointers.
     *
     * @template T of BaseObject
     *
     * @param  T  $object
     * @return T
     */
    public function resolve(BaseObject $object): BaseObject
    {
        return $object->resolveRefs($this->pointer(...));
    }

    /** The registered components, null when nothing is referenced. */
    public function toObject(): ?Objects\Components
    {
        return $this->schemas === [] ? null : Objects\Components::create()->schemas(...array_values($this->schemas));
    }

    private function pointer(string $class): string
    {
        if (isset($this->names[$class])) {
            return "#/components/schemas/{$this->names[$class]}";
        }

        $schema = $this->factory->build($class);
        $name = (string) $schema->getObjectId();
        $other = array_search($name, $this->names, true);

        if ($other !== false) {
            throw new InvalidOpenapiDefinition("Classes [{$other}] and [{$class}] are both named [{$name}] in components.schemas, set #[Schema(name: …)] on one of them.");
        }

        // registered before resolving its own references, so a cycle stops on the pointer; the key keeps the discovery order
        $this->names[$class] = $name;
        $this->schemas[$name] = $schema;
        $this->schemas[$name] = $this->resolve($schema);

        return "#/components/schemas/{$name}";
    }
}

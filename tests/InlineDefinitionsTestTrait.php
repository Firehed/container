<?php

declare(strict_types=1);

namespace Firehed\Container;

trait InlineDefinitionsTestTrait
{
    abstract protected function getBuilder(): BuilderInterface;

    /**
     * @return mixed[]
     */
    public function getInlineDefinitions(): array
    {
        return [
            'TestLiteral' => 'abc123',
            Fixtures\ExplicitDefinitionInterface::class => Fixtures\ExplicitDefinition::class,
            Fixtures\ExplicitDefinition::class,
        ];
    }

    public function testInlineWiring(): void
    {
        $builder = $this->getBuilder();
        $builder->addFile('tests/ValidDefinitions/Literals.php');
        $builder->addDefinitions($this->getInlineDefinitions());
        $c = $builder->build();

        $this->assertSame('UnitTest', $c->get('string_literal'), 'Should have definition from file');
        $this->assertSame('abc123', $c->get('TestLiteral'), 'Should have inline definition');

        $fed = $c->get(Fixtures\ExplicitDefinition::class);
        $this->assertInstanceOf(Fixtures\ExplicitDefinition::class, $fed);
        $fedi = $c->get(Fixtures\ExplicitDefinitionInterface::class);
        $this->assertSame($fedi, $fed, 'Interface to impl should point to the same instance');
    }
}

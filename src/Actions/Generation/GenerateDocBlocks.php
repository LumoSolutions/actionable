<?php

namespace LumoSolutions\Actionable\Actions\Generation;

use Exception;
use LumoSolutions\Actionable\Dtos\Generation\DocBlockGenDto;
use LumoSolutions\Actionable\Support\DocBlockHelper;
use LumoSolutions\Actionable\Support\DocBlockProcessor;
use LumoSolutions\Actionable\Traits\IsRunnable;

class GenerateDocBlocks
{
    use IsRunnable;

    private const string METHOD_RUN = 'run';

    private const string METHOD_DISPATCH = 'dispatch';

    private const string METHOD_DISPATCH_ON = 'dispatchOn';

    /**
     * @throws Exception
     */
    public function handle(DocBlockGenDto $dto): array
    {
        $processor = new DocBlockProcessor($dto->docBlocks);

        $processor->removeMethodsIf(self::METHOD_RUN, ! $dto->isRunnable || ! $dto->handle);
        $processor->removeMethodsIf(self::METHOD_DISPATCH, ! $dto->isDispatchable || ! $dto->handle);
        $processor->removeMethodsIf(self::METHOD_DISPATCH_ON, ! $dto->isDispatchable || ! $dto->handle);

        if ($dto->handle) {
            if ($dto->isRunnable) {
                $processor->addOrReplaceMethod(self::METHOD_RUN, $this->buildRunMethod($dto));
            }

            if ($dto->isDispatchable) {
                $processor->addOrReplaceMethod(self::METHOD_DISPATCH, $this->buildDispatchMethod($dto));
                $processor->addOrReplaceMethod(self::METHOD_DISPATCH_ON, $this->buildDispatchOnMethod($dto));
            }
        }

        return $processor->getDocBlocks();
    }

    protected function buildRunMethod(DocBlockGenDto $dto): ?string
    {
        return DocBlockHelper::buildMethodLine(
            'static',
            DocBlockHelper::formatReturnType(
                $dto->handle->returnTypes,
                $dto->usings
            ),
            self::METHOD_RUN,
            DocBlockHelper::formatParameters(
                $dto->handle->parameters,
                $dto->usings
            )
        );
    }

    protected function buildDispatchMethod(DocBlockGenDto $dto): ?string
    {
        return DocBlockHelper::buildMethodLine(
            'static',
            'void',
            self::METHOD_DISPATCH,
            DocBlockHelper::formatParameters(
                $dto->handle->parameters,
                $dto->usings
            )
        );
    }

    protected function buildDispatchOnMethod(DocBlockGenDto $dto): ?string
    {
        $parameters = DocBlockHelper::formatParameters($dto->handle->parameters, $dto->usings);
        $queueParameter = 'string $queue';

        return DocBlockHelper::buildMethodLine(
            'static',
            'void',
            self::METHOD_DISPATCH_ON,
            $parameters
                ? $queueParameter.', '.$parameters
                : $queueParameter
        );
    }
}

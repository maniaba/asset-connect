<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\BooleanAnd\SimplifyEmptyArrayCheckRector;
use Rector\CodeQuality\Rector\Empty_\SimplifyEmptyCheckOnEmptyArrayRector;
use Rector\CodeQuality\Rector\Expression\InlineIfToExplicitIfRector;
use Rector\CodeQuality\Rector\FuncCall\ChangeArrayPushToArrayAssignRector;
use Rector\CodeQuality\Rector\FuncCall\SingleInArrayToCompareRector;
use Rector\CodeQuality\Rector\If_\SimplifyIfReturnBoolRector;
use Rector\CodeQuality\Rector\Ternary\TernaryEmptyArrayArrayDimFetchToCoalesceRector;
use Rector\CodeQuality\Rector\Ternary\UnnecessaryTernaryExpressionRector;
use Rector\CodingStyle\Rector\ClassMethod\FuncGetArgsToVariadicParamRector;
use Rector\CodingStyle\Rector\ClassMethod\MakeInheritedMethodVisibilitySameAsParentRector;
use Rector\CodingStyle\Rector\FuncCall\VersionCompareFuncCallToConstantRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPromotedPropertyRector;
use Rector\EarlyReturn\Rector\If_\ChangeIfElseValueAssignToEarlyReturnRector;
use Rector\EarlyReturn\Rector\If_\RemoveAlwaysElseRector;
use Rector\EarlyReturn\Rector\Return_\PreparedValueToEarlyReturnRector;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Rector\Php73\Rector\FuncCall\StringifyStrNeedlesRector;
use Rector\PHPUnit\AnnotationsToAttributes\Rector\Class_\AnnotationWithValueToAttributeRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\YieldDataProviderRector;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Privatization\Rector\Property\PrivatizeFinalClassPropertyRector;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromAssignsRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src/',
        __DIR__ . '/tests/',
    ])
    ->withAutoloadPaths([
        __DIR__ . '/vendor/autoload.php',
    ])
    ->withBootstrapFiles([
        __DIR__ . '/vendor/codeigniter4/framework/system/Test/bootstrap.php',
    ])
    ->withPHPStanConfigs([
        __DIR__ . '/phpstan.neon.dist',
        __DIR__ . '/vendor/phpstan/phpstan-strict-rules/rules.neon',
    ])
    ->withCache(is_dir('/tmp') ? '/tmp/rector' : null)
    ->withParallel()
    ->withImportNames(removeUnusedImports: false)
    ->withPhpSets()
    ->withSets([
        SetList::DEAD_CODE,
        PHPUnitSetList::PHPUNIT_CODE_QUALITY,
    ])
    ->withComposerBased(phpunit: true)
    ->withSkip([
        __DIR__ . '/src/Views',

        StringifyStrNeedlesRector::class,
        YieldDataProviderRector::class,

        RemoveUnusedPromotedPropertyRector::class,
        AnnotationWithValueToAttributeRector::class,

        // May load view files directly when detecting classes
        StringClassNameToClassConstantRector::class,

        TypedPropertyFromAssignsRector::class => [
            __DIR__ . '/src/Asset/Asset.php',
            __DIR__ . '/src/Models/AssetModel.php',
            __DIR__ . '/src/Models/BaseModel.php',
            __DIR__ . '/src/AssetVariants/AssetVariant.php',
            __DIR__ . '/src/Exceptions/AssetException.php',
            __DIR__ . '/src/Commands/MigrateLegacyAssetPaths.php',
            __DIR__ . '/src/Commands/StorageLink.php',

            // Tests
            __DIR__ . '/tests/AssetCollection/SetupAssetCollectionTest.php',
            __DIR__ . '/tests/_support/Entities/FakeAssetEntity.php',
            __DIR__ . '/tests/_support/Models/FakeAssetEntityModel.php',
            __DIR__ . '/tests/_support/Models/InvalidAssetConnectReturnTypeModel.php',
        ],
    ])
    ->withRules([
        RemoveAlwaysElseRector::class,
        ChangeIfElseValueAssignToEarlyReturnRector::class,
        SimplifyIfReturnBoolRector::class,
        InlineIfToExplicitIfRector::class,
        PreparedValueToEarlyReturnRector::class,
        ChangeArrayPushToArrayAssignRector::class,
        UnnecessaryTernaryExpressionRector::class,
        FuncGetArgsToVariadicParamRector::class,
        MakeInheritedMethodVisibilitySameAsParentRector::class,
        SimplifyEmptyArrayCheckRector::class,
        SimplifyEmptyCheckOnEmptyArrayRector::class,
        TernaryEmptyArrayArrayDimFetchToCoalesceRector::class,
        StringClassNameToClassConstantRector::class,
        PrivatizeFinalClassPropertyRector::class,
        SingleInArrayToCompareRector::class,
        VersionCompareFuncCallToConstantRector::class,
    ])
    ->withConfiguredRule(TypedPropertyFromAssignsRector::class, [
        TypedPropertyFromAssignsRector::INLINE_PUBLIC => true,
    ]);

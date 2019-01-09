<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace AppBundle\Component\Shipping\Calculator;


use Sylius\Component\Shipping\Calculator\CalculatorInterface;
use Sylius\Component\Shipping\Model\ShipmentInterface;

final class PerWeightRateCalculator implements CalculatorInterface
{

    /**
     * {@inheritdoc}
     */
    public function calculate(ShipmentInterface $subject, array $configuration): int
    {
        $list = $configuration['prices'];
        foreach ($list as $row) {
            $rates[$row['weight']] = $row['amount'];
        }
        ksort($rates);
        $totalWeight = 0;
        foreach ($subject->getUnits() as $item) {
            $totalWeight += (int) $item->getShippable()->getShippingWeight();
        }

        foreach ($rates as $k => $v) {
            if ($totalWeight < $k) {
                return $v;
            }
        }

        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return 'per_weight_rate';
    }
}
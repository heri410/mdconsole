<?php

use App\Models\Position;

describe('Business Calculations', function () {
    describe('Position Amount Calculations', function () {
        it('calculates simple total without discount', function () {
            $position = Position::make([
                'quantity' => 5.00,
                'unit_price' => 100.00,
                'discount' => 0.00,
            ]);

            expect($position->total_amount)->toBe(500.00);
            expect($position->discount_amount)->toBe(0.00);
        });

        it('calculates total with percentage discount', function () {
            $position = Position::make([
                'quantity' => 10.00,
                'unit_price' => 50.00,
                'discount' => 20.00, // 20%
            ]);

            // 10 * 50 = 500, 20% discount = 100, total = 400
            expect($position->total_amount)->toBe(400.00);
            expect($position->discount_amount)->toBe(100.00);
        });

        it('handles decimal quantities correctly', function () {
            $position = Position::make([
                'quantity' => 2.5,
                'unit_price' => 40.00,
                'discount' => 10.00, // 10%
            ]);

            // 2.5 * 40 = 100, 10% discount = 10, total = 90
            expect($position->total_amount)->toBe(90.00);
            expect($position->discount_amount)->toBe(10.00);
        });

        it('handles decimal unit prices correctly', function () {
            $position = Position::make([
                'quantity' => 3.00,
                'unit_price' => 33.33,
                'discount' => 0.00,
            ]);

            // 3 * 33.33 = 99.99
            expect($position->total_amount)->toBe(99.99);
        });

        it('rounds calculations to 2 decimal places consistently', function () {
            $position = Position::make([
                'quantity' => 3.33,
                'unit_price' => 33.33,
                'discount' => 33.33,
            ]);

            // 3.33 * 33.33 = 110.9889
            // 33.33% discount = 36.9926...
            // Rounded discount = 36.99
            // Rounded total = 110.99 - 36.99 = 73.99
            expect($position->discount_amount)->toBe(36.99);
            expect($position->total_amount)->toBe(73.99);
        });

        it('handles maximum discount (100%)', function () {
            $position = Position::make([
                'quantity' => 5.00,
                'unit_price' => 100.00,
                'discount' => 100.00, // 100%
            ]);

            expect($position->discount_amount)->toBe(500.00);
            expect($position->total_amount)->toBe(0.00);
        });

        it('handles very small amounts', function () {
            $position = Position::make([
                'quantity' => 0.01,
                'unit_price' => 0.01,
                'discount' => 50.00, // 50%
            ]);

            // 0.01 * 0.01 = 0.0001
            // 50% discount = 0.00005
            // Rounded values should be 0.00
            expect($position->discount_amount)->toBe(0.00);
            expect($position->total_amount)->toBe(0.00);
        });

        it('handles large amounts', function () {
            $position = Position::make([
                'quantity' => 1000.00,
                'unit_price' => 999.99,
                'discount' => 5.55, // 5.55%
            ]);

            // 1000 * 999.99 = 999990
            // 5.55% discount = 55499.445
            // Rounded discount = 55499.45
            // Total = 999990.00 - 55499.45 = 944490.55
            expect($position->discount_amount)->toBe(55499.45);
            expect($position->total_amount)->toBe(944490.55);
        });
    });

    describe('Real-world Calculation Scenarios', function () {
        it('calculates web development hourly work correctly', function () {
            $position = Position::make([
                'name' => 'Frontend Development',
                'quantity' => 40.5, // 40.5 hours
                'unit_name' => 'hours',
                'unit_price' => 85.00, // €85/hour
                'discount' => 7.5, // 7.5% bulk discount
            ]);

            // 40.5 * 85 = 3442.50
            // 7.5% discount = 258.1875 → 258.19
            // Total = 3442.50 - 258.19 = 3184.31
            expect($position->discount_amount)->toBe(258.19);
            expect($position->total_amount)->toBe(3184.31);
        });

        it('calculates consulting daily rate correctly', function () {
            $position = Position::make([
                'name' => 'Technical Consulting',
                'quantity' => 3.00, // 3 days
                'unit_name' => 'days',
                'unit_price' => 750.00, // €750/day
                'discount' => 12.00, // 12% project discount
            ]);

            // 3 * 750 = 2250
            // 12% discount = 270
            // Total = 1980
            expect($position->discount_amount)->toBe(270.00);
            expect($position->total_amount)->toBe(1980.00);
        });

        it('calculates monthly service fee correctly', function () {
            $position = Position::make([
                'name' => 'Monthly Maintenance',
                'quantity' => 12.00, // 12 months
                'unit_name' => 'months',
                'unit_price' => 199.99, // €199.99/month
                'discount' => 15.00, // 15% annual discount
            ]);

            // 12 * 199.99 = 2399.88
            // 15% discount = 359.982 → 359.98
            // Total = 2399.88 - 359.98 = 2039.90
            expect($position->discount_amount)->toBe(359.98);
            expect($position->total_amount)->toBe(2039.90);
        });

        it('handles mixed service types in workflow', function () {
            // Simulate multiple positions for the same customer
            $positions = collect([
                Position::make([
                    'name' => 'Initial Setup',
                    'quantity' => 8.0,
                    'unit_price' => 95.00,
                    'discount' => 0.00,
                ]),
                Position::make([
                    'name' => 'Development Work',
                    'quantity' => 45.5,
                    'unit_price' => 85.00,
                    'discount' => 10.00,
                ]),
                Position::make([
                    'name' => 'Project Management',
                    'quantity' => 20.0,
                    'unit_price' => 120.00,
                    'discount' => 5.00,
                ]),
            ]);

            $totalAmount = $positions->sum('total_amount');
            $totalDiscount = $positions->sum('discount_amount');

            // Position 1: 8 * 95 = 760.00 (no discount)
            // Position 2: 45.5 * 85 = 3867.50, 10% discount = 386.75, total = 3480.75
            // Position 3: 20 * 120 = 2400.00, 5% discount = 120.00, total = 2280.00
            // Grand total: 760.00 + 3480.75 + 2280.00 = 6520.75
            // Total discount: 0.00 + 386.75 + 120.00 = 506.75

            expect($totalAmount)->toBe(6520.75);
            expect($totalDiscount)->toBe(506.75);
        });
    });

    describe('Edge Cases and Error Conditions', function () {
        it('handles zero quantity', function () {
            $position = Position::make([
                'quantity' => 0.00,
                'unit_price' => 100.00,
                'discount' => 25.00,
            ]);

            expect($position->total_amount)->toBe(0.00);
            expect($position->discount_amount)->toBe(0.00);
        });

        it('handles zero unit price', function () {
            $position = Position::make([
                'quantity' => 10.00,
                'unit_price' => 0.00,
                'discount' => 50.00,
            ]);

            expect($position->total_amount)->toBe(0.00);
            expect($position->discount_amount)->toBe(0.00);
        });

        it('maintains precision with complex calculations', function () {
            // Test multiple operations that could introduce floating point errors
            $position = Position::make([
                'quantity' => 7.777,
                'unit_price' => 77.77,
                'discount' => 7.77,
            ]);

            // 7.777 * 77.77 = 604.79829
            // 7.77% discount = 46.992744993 → 46.99
            // Total = 604.80 - 46.99 = 557.81
            
            // These should be rounded consistently
            $subtotal = round($position->quantity * $position->unit_price, 2);
            $discount = round($subtotal * ($position->discount / 100), 2);
            $total = round($subtotal - $discount, 2);

            expect($position->discount_amount)->toBe($discount);
            expect($position->total_amount)->toBe($total);
        });

        it('ensures calculations are always positive or zero', function () {
            $position = Position::make([
                'quantity' => 1.00,
                'unit_price' => 1.00,
                'discount' => 100.00, // Full discount
            ]);

            expect($position->total_amount)->toBeGreaterThanOrEqual(0.00);
            expect($position->discount_amount)->toBeGreaterThanOrEqual(0.00);
        });
    });

    describe('Performance and Scalability', function () {
        it('handles batch calculations efficiently', function () {
            // Create many positions to test calculation performance
            $positions = collect();
            
            for ($i = 0; $i < 1000; $i++) {
                $positions->push(Position::make([
                    'quantity' => rand(1, 100) / 10, // 0.1 to 10.0
                    'unit_price' => rand(1000, 50000) / 100, // €10.00 to €500.00
                    'discount' => rand(0, 2500) / 100, // 0% to 25%
                ]));
            }

            $startTime = microtime(true);
            
            $totalAmount = $positions->sum('total_amount');
            $totalDiscount = $positions->sum('discount_amount');
            
            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;

            // Should complete in reasonable time (under 1 second)
            expect($executionTime)->toBeLessThan(1.0);
            
            // Results should be numeric and reasonable
            expect($totalAmount)->toBeFloat();
            expect($totalDiscount)->toBeFloat();
            expect($totalAmount)->toBeGreaterThan(0);
            expect($totalDiscount)->toBeGreaterThanOrEqual(0);
        });
    });
});
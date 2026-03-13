<?php

namespace App\Services;

/**
 * AC-3 Constraint Propagation Algorithm
 * Reduces domains by removing inconsistent values early
 */
class ConstraintPropagator
{
    private ConstraintValidator $validator;

    public function __construct(ConstraintValidator $validator)
    {
        $this->validator = $validator;
    }

    /**
     * Apply AC-3 algorithm to reduce domains
     * Returns updated domains with pruned values
     */
    public function propagate(&$domains, $constraints): bool
    {
        $queue = $this->buildQueue($constraints);

        while (!empty($queue)) {
            list($xi, $xj) = array_shift($queue);

            if ($this->revise($domains, $xi, $xj)) {
                if (empty($domains[$xi])) {
                    return false; // No solution possible
                }

                // Get neighbors (other variables that have constraints with xi)
                $neighbors = $this->getNeighbors($xi, $constraints);
                foreach ($neighbors as $xk) {
                    if ($xk !== $xj) {
                        $queue[] = [$xk, $xi];
                    }
                }
            }
        }

        return true;
    }

    /**
     * Remove values from domain of Xi that have no consistent assignment in Xj
     */
    private function revise(&$domains, $xi, $xj): bool
    {
        $revised = false;

        foreach ($domains[$xi] as $key => $value) {
            if (!$this->hasConsistentValue($value, $domains[$xj])) {
                unset($domains[$xi][$key]);
                $revised = true;
            }
        }

        return $revised;
    }

    /**
     * Check if a value in Xi has a consistent assignment in Xj
     */
    private function hasConsistentValue($xiValue, $xjDomain): bool
    {
        foreach ($xjDomain as $xjValue) {
            // Check if this pair is consistent (doesn't violate constraints)
            if (!$this->constraintViolated($xiValue, $xjValue)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if two values violate constraints
     */
    private function constraintViolated($val1, $val2): bool
    {
        // Same room at same time = violation
        if ($val1['room_id'] === $val2['room_id'] &&
            $val1['time_slot_id'] === $val2['time_slot_id'] &&
            $val1['day_of_week'] === $val2['day_of_week']) {
            return true;
        }

        // Same teacher at same time = violation
        if ($val1['teacher_id'] === $val2['teacher_id'] &&
            $val1['time_slot_id'] === $val2['time_slot_id'] &&
            $val1['day_of_week'] === $val2['day_of_week']) {
            return true;
        }

        // Same class at same time = violation
        if ($val1['class_id'] === $val2['class_id'] &&
            $val1['time_slot_id'] === $val2['time_slot_id'] &&
            $val1['day_of_week'] === $val2['day_of_week']) {
            return true;
        }

        return false;
    }

    /**
     * Build initial queue of (variable, variable) pairs
     */
    private function buildQueue($constraints): array
    {
        $queue = [];
        foreach ($constraints as $constraint) {
            if (isset($constraint['xi'], $constraint['xj'])) {
                $queue[] = [$constraint['xi'], $constraint['xj']];
                $queue[] = [$constraint['xj'], $constraint['xi']];
            }
        }
        return $queue;
    }

    /**
     * Get neighbors of a variable (variables with constraints involving it)
     */
    private function getNeighbors($variable, $constraints): array
    {
        $neighbors = [];
        foreach ($constraints as $constraint) {
            if (($constraint['xi'] ?? null) === $variable) {
                $neighbors[] = $constraint['xj'];
            } elseif (($constraint['xj'] ?? null) === $variable) {
                $neighbors[] = $constraint['xi'];
            }
        }
        return array_unique($neighbors);
    }
}

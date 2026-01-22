<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class QueueRuleEngine
{
    public function evaluate(array|object $context): ?string
    {
        $ctx = $this->normalizeContext($context);

        $rules = DB::table('queue_rules')
            ->where('is_active', 1)
            ->orderBy('priority')
            ->get();

        foreach ($rules as $rule) {
            if ($this->matchesRule($rule, $ctx)) {
                return $rule->action_code;
            }
        }

        return null;
    }

    private function normalizeContext(array|object $context): object
    {
        $ctx = is_array($context) ? (object) $context : $context;

        return (object) [
            'ewt' => $ctx->ewt ?? null,
            'distance' => $ctx->distance ?? null,
            'inside_geofence' => $ctx->inside_geofence ?? null,
            'countdown_ended' => $ctx->countdown_ended ?? null,
        ];
    }

    private function matchesRule(object $rule, object $context): bool
    {
        if (!$this->matchesRange($context->ewt, $rule->ewt_min, $rule->ewt_max)) {
            return false;
        }

        if (!$this->matchesRange($context->distance, $rule->distance_min, $rule->distance_max)) {
            return false;
        }

        if (!$this->matchesOptionalBoolean($context->inside_geofence, $rule->inside_geofence)) {
            return false;
        }

        if (!$this->matchesOptionalBoolean($context->countdown_ended, $rule->requires_countdown_end)) {
            return false;
        }

        return true;
    }

    private function matchesRange($value, $min, $max): bool
    {
        if ($value === null) {
            return false;
        }

        if ($min !== null && $value < $min) {
            return false;
        }

        if ($max !== null && $value > $max) {
            return false;
        }

        return true;
    }

    private function matchesOptionalBoolean($contextValue, $ruleValue): bool
    {
        if ($ruleValue === null) {
            return true;
        }

        return (bool) $contextValue === (bool) $ruleValue;
    }
}

<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class StructuralChangeRequiresConfirmationException extends Exception
{
    public function __construct(
        $message = "The uploaded program structure differs from the current version. Proceeding will create a new version and reset the plan to draft. This cannot be undone.",
        $code = 0,
        Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    // Optional: customize how it's rendered in views (e.g., for friendly error messages)
    public function render($request)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'error' => $this->getMessage(),
                'requires_confirmation' => true,
            ], 422);
        }

        return redirect()->back()
            ->with('error', $this->getMessage())
            ->with('requires_structural_confirmation', true);
    }
}

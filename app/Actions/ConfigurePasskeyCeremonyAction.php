<?php

namespace App\Actions;

use Spatie\LaravelPasskeys\Actions\ConfigureCeremonyStepManagerFactoryAction;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;

/**
 * Configures the WebAuthn ceremony validator with the correct allowed origins.
 *
 * The default action leaves allowedOrigins null, falling back to the deprecated
 * CheckOrigin step which rejects HTTP origins. Setting allowedOrigins explicitly
 * switches to CheckAllowedOrigins which does an exact match against APP_URL,
 * supporting both HTTP dev environments and HTTPS production correctly.
 */
class ConfigurePasskeyCeremonyAction extends ConfigureCeremonyStepManagerFactoryAction
{
    public function execute(): CeremonyStepManagerFactory
    {
        $factory = parent::execute();

        // Set the exact origin the browser will report in clientDataJSON.
        // This replaces the deprecated CheckOrigin step (which required HTTPS
        // unless securedRelyingPartyId was set) with the newer CheckAllowedOrigins.
        $factory->setAllowedOrigins([config('app.url')]);

        return $factory;
    }
}

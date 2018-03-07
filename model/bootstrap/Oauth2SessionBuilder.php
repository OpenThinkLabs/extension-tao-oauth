<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA
 *
 */

namespace oat\taoOauth\model\bootstrap;

use oat\oatbox\user\LoginFailedException;
use oat\tao\model\routing\Resolver;
use oat\tao\model\session\restSessionFactory\SessionBuilder;
use oat\taoOauth\model\Oauth2Service;
use oat\taoOauth\model\OauthController;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

class Oauth2SessionBuilder implements SessionBuilder, ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * Check if the current builder is able to load the session.
     *
     * The $request and $resolver is used to know ig the called controller is an oauth controller
     *
     * @param \common_http_Request $request
     * @param Resolver $resolver
     * @return bool
     */
    public function isApplicable(\common_http_Request $request, Resolver $resolver)
    {
        return is_subclass_of($resolver->getControllerClass(), OauthController::class);
    }

    /**
     * Construct the session based on request
     *
     * Validate the request by verify the token
     * Create the user from oauth consumer associated to the token
     *
     * @param \common_http_Request $request
     * @return \common_session_RestSession|\common_session_Session
     * @throws LoginFailedException
     */
    public function getSession(\common_http_Request $request)
    {
        try {
            $user = $this->getOauth2Service()
                ->validate($request)
                ->getConsumer();
            return new \common_session_RestSession(
                new \core_kernel_users_GenerisUser($user)
            );
        } catch (\common_http_InvalidSignatureException $e) {
            throw new LoginFailedException([$e->getMessage()]);
        }
    }

    /**
     * Get the oauth2 service
     *
     * @return Oauth2Service
     */
    protected function getOauth2Service()
    {
        return $this->getServiceLocator()->get(Oauth2Service::SERVICE_ID);
    }

}
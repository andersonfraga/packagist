<?php

/*
 * This file is part of Packagist.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *     Nils Adermann <naderman@naderman.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Packagist\WebBundle\Controller;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use FOS\UserBundle\Model\UserInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Packagist\WebBundle\Entity\User;
use Packagist\WebBundle\Entity\Package;
use Packagist\WebBundle\Model\RedisAdapter;

/**
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class UserController extends Controller
{
    /**
     * @Template()
     * @Route("/users/{name}/packages/", name="user_packages")
     * @ParamConverter("user", options={"mapping": {"name": "username"}})
     */
    public function packagesAction(Request $req, User $user)
    {
        $packages = $this->getUserPackages($req, $user);

        return array(
            'packages' => $packages,
            'user' => $user,
        );
    }

    public function myProfileAction(Request $req)
    {
        $user = $this->container->get('security.context')->getToken()->getUser();
        if (!is_object($user) || !$user instanceof UserInterface) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        $packages = $this->getUserPackages($req, $user);

        return $this->container->get('templating')->renderResponse(
            'FOSUserBundle:Profile:show.html.'.$this->container->getParameter('fos_user.template.engine'),
            array(
                'packages' => $packages,
                'user' => $user,
            )
        );
    }


    /**
     * @Template()
     * @Route("/users/{name}/", name="user_profile")
     * @ParamConverter("user", options={"mapping": {"name": "username"}})
     */
    public function profileAction(Request $req, User $user)
    {
        $packages = $this->getUserPackages($req, $user);

        return array(
            'packages' => $packages,
            'user' => $user,
        );
    }

    protected function getUserPackages($req, $user)
    {
        $packages = $this->getDoctrine()
            ->getRepository('PackagistWebBundle:Package')
            ->getFilteredQueryBuilder(array('maintainer' => $user->getId()))
            ->orderBy('p.name');

        $paginator = new Pagerfanta(new DoctrineORMAdapter($packages, true));
        $paginator->setMaxPerPage(15);
        $paginator->setCurrentPage($req->query->get('page', 1), false, true);

        return $paginator;
    }
}
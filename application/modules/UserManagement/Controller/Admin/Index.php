<?php

namespace UserManagement\Controller\Admin;

use Micro\Application\Controller;
use Micro\Application\View;
use Micro\Auth\Auth;
use Micro\Http\Response\RedirectResponse;
use Micro\Application\Security;
use Micro\Form\Form;
use UserManagement\Model\Users;

class Index extends Controller
{
    protected $scope = 'admin';

    public function profileAction()
    {
        $form = new Form(module_path('UserManagement', 'Resources/forms/admin/profile.php'));

        $form->username->setValue(identity()->getUsername());

        if ($this->request->isPost()) {

            $data = $this->request->getPost();

            if (isset($data['btnBack'])) {
                return new RedirectResponse(route());
            }

            if ($form->isValid($data)) {

                $usersModel = new Users();
                $user = $usersModel->find(identity()->getId());

                if ($user && $data['password']) {
                    $user->password = Security::hash($data['password']);
                    $usersModel->save($user);
                }

                $redirect = new RedirectResponse(route());

                return $redirect->withFlash();
            }
        }

        return new View('admin/index/profile', ['form' => $form]);
    }

    public function loginAction()
    {
        $form = new Form(module_path('UserManagement', 'Resources/forms/admin/login.php'));

        if ($this->request->isPost()) {
            $data = $this->request->getPost();
            if ($form->isValid($data)) {
                $usersModel = new Users();
                if ($usersModel->login($data['username'], $data['password'])) {
                    if (($backTo = $this->request->getParam('backTo')) !== \null) {
                        return new RedirectResponse(urldecode($backTo));
                    } else {
                        return new RedirectResponse(route('admin', [], \true));
                    }
                } else {
                    $form->password->addError('Невалидни данни');
                }
            }
        }

        return ['form' => $form];
    }

    public function logoutAction()
    {
        Auth::getInstance()->clearIdentity();

        return new RedirectResponse(route('admin', [], \true));
    }
}
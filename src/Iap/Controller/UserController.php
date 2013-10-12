<?php

namespace Iap\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Form\Annotation\AnnotationBuilder;
use Zend\View\Model\ViewModel;
use Iap\Forms;

class UserController extends AbstractActionController
{

    protected $storage;
    protected $authservice;
    protected $config;

    public function indexAction()
    {
        $authService = $this->getAuthService();
        if (!$authService->hasIdentity()) {
            return $this->redirect()->toRoute('iap/login');
        }
        return new ViewModel();
    }

    public function getAuthService()
    {
        if (!$this->authservice) {
            $this->authservice = $this->getServiceLocator()->get('IapService');
        }
        return $this->authservice;
    }

    public function getSessionStorage()
    {
        if (!$this->storage) {
            $this->storage = $this->getServiceLocator()->get('Iap\Storage');
        }
        return $this->storage;
    }

    public function getConfig()
    {
        if (!$this->config) {
            $this->config = $this->getServiceLocator()->get('Iap\Config');
        }
        return $this->config;
    }

    public function loginAction()
    {
        //if already login, redirect to success page
        if ($this->getAuthService()->hasIdentity()) {
            return $this->redirect()->toRoute('iap');
        }
        $user = new Forms\Login();
        $builder = new AnnotationBuilder();
        $form = $builder->createForm($user);

        return array(
            'form' => $form,
            'messages' => $this->flashmessenger()->getMessages()
        );
    }

    public function authenticateAction()
    {
        $user = new Forms\Login();
        $builder = new AnnotationBuilder();
        $form = $builder->createForm($user);
        $request = $this->getRequest();
        if ($request->isPost()) {
            $form->setData($request->getPost());
            if ($form->isValid()) {
                $user = $request->getPost('username');
                $pass = $request->getPost('password');
                $credentials = array('username' => $user, 'password' => $pass,);
                $result = $this->getAuthService()->authenticate($credentials);

                foreach ($result->getMessages() as $message) {
                    //save message temporary into flashmessenger
                    $this->flashmessenger()->addMessage($message);
                }

                if ($result->hasIdentity()) {
                    return $this->redirect()->toRoute('iap');
                }
            }
        }
        return $this->redirect()->toRoute('iap/login');
    }

    public function logoutAction()
    {
        if ($this->getAuthService()->hasIdentity()) {
            $this->getAuthService()->clearIdentity();
            $this->flashmessenger()->addMessage("You've been logged out");
        }

        return $this->redirect()->toRoute('iap/login');
    }
}
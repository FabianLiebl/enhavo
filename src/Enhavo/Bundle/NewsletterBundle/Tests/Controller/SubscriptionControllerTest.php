<?php


namespace Controller;


use Enhavo\Bundle\NewsletterBundle\Controller\SubscriptionController;
use Enhavo\Bundle\NewsletterBundle\Entity\PendingSubscriber;
use Enhavo\Bundle\NewsletterBundle\Model\Subscriber;
use Enhavo\Bundle\NewsletterBundle\Model\SubscriberInterface;
use Enhavo\Bundle\NewsletterBundle\Pending\PendingSubscriberManager;
use Enhavo\Bundle\NewsletterBundle\Storage\Storage;
use Enhavo\Bundle\NewsletterBundle\Strategy\Strategy;
use Enhavo\Bundle\NewsletterBundle\Subscription\Subscription;
use Enhavo\Bundle\NewsletterBundle\Subscription\SubscriptionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

class SubscriptionControllerTest extends TestCase
{
    private function createDependencies(): SubscriptionControllerTestDependencies
    {
        $dep = new SubscriptionControllerTestDependencies();
        $dep->subscriptionManager = $this->getMockBuilder(SubscriptionManager::class)->disableOriginalConstructor()->getMock();
        $dep->pendingManager = $this->getMockBuilder(PendingSubscriberManager::class)->disableOriginalConstructor()->getMock();
        $dep->translator = $this->getMockBuilder(TranslatorInterface::class)->getMock();
        $dep->container = $this->getMockBuilder(Container::class)->disableOriginalConstructor()->getMock();

        return $dep;
    }


    private function createInstance(SubscriptionControllerTestDependencies $dependencies): SubscriptionController
    {
        $controller = new SubscriptionTestController(
            $dependencies->subscriptionManager,
            $dependencies->pendingManager,
            $dependencies->translator
        );
        $controller->setContainer($dependencies->container);
        return $controller;
    }

    private function createSubscription($key)
    {
        /** @var Strategy|MockObject $strategy */
        $strategy = $this->getMockBuilder(Strategy::class)->disableOriginalConstructor()->getMock();
        $storage = $this->getMockBuilder(Storage::class)->disableOriginalConstructor()->getMock();
        $strategy->method('getStorage')->willReturn($storage);

        $formConfig = [

        ];

        return new Subscription($key, $strategy, Subscriber::class, $formConfig);
    }

    public function testActivateActionNotFound()
    {
        $dep = $this->createDependencies();
        $controller = $this->createInstance($dep);
        $request = new Request([
            'type' => 'default',
            'token' => '__TOKEN__'
        ]);

        $this->expectException(NotFoundHttpException::class);
        $controller->activateAction($request);
    }

    public function testActivateAction()
    {
        $dependencies = $this->createDependencies();

        $pendingSubscriber = new PendingSubscriber();
        $pendingSubscriber->setData(new Subscriber());

        $subscription = $this->createSubscription('default');
        $subscription->getStrategy()->expects($this->once())->method('activateSubscriber');
        $subscription->getStrategy()->expects($this->once())->method('getActivationTemplate')->willReturn('tpl.html.twig');
        $dependencies->subscriptionManager->method('getSubscription')->willReturn($subscription);
        $dependencies->pendingManager->method('findByToken')->willReturn($pendingSubscriber);

        $controller = $this->createInstance($dependencies);

        $request = new Request([
            'type' => 'default',
            'token' => '__TOKEN__'
        ]);

        $result = $controller->activateAction($request);

        $this->assertEquals('tpl.html.twig.rendered', $result->getContent());
    }

    public function testAddAction()
    {
        $dependencies = $this->createDependencies();
        $form = $this->getMockBuilder(FormInterface::class)->getMock();
        $form->expects($this->once())->method('handleRequest');
        $form->expects($this->once())->method('isValid')->willReturn(true);

        $dependencies->subscriptionManager->expects($this->once())->method('createForm')->willReturn($form);
        $subscription = $this->createSubscription('default');
        $subscription->getStrategy()->expects($this->once())->method('addSubscriber')->willReturn('added');

        $dependencies->translator->expects($this->once())->method('trans')->willReturnCallback(function ($message) {
            return $message .'.trans';
        });
        $dependencies->subscriptionManager->method('getSubscription')->willReturn($subscription);

        $controller = $this->createInstance($dependencies);
        $request = new Request([
            'type' => 'default',
        ]);

        $response = $controller->addAction($request);
        $this->assertEquals('{"message":"added.trans","subscriber":{}}', $response->getContent());
    }

    public function testAddActionInvalid()
    {
        $dependencies = $this->createDependencies();
        $form = $this->getMockBuilder(FormInterface::class)->getMock();
        $form->expects($this->once())->method('handleRequest');
        $form->expects($this->once())->method('isValid')->willReturn(false);
        $form->expects($this->once())->method('getErrors')->willReturn([]);

        $dependencies->subscriptionManager->expects($this->once())->method('createForm')->willReturn($form);
        $subscription = $this->createSubscription('default');
        $subscription->getStrategy()->expects($this->never())->method('addSubscriber');
        $dependencies->translator->expects($this->never())->method('trans');
        $dependencies->subscriptionManager->method('getSubscription')->willReturn($subscription);

        $controller = $this->createInstance($dependencies);
        $request = new Request([
            'type' => 'default',
        ]);

        $response = $controller->addAction($request);
        $this->assertEquals('{"errors":[],"subscriber":{}}', $response->getContent());
    }

    public function testUnsubscribeAction()
    {
        $dependencies = $this->createDependencies();
        $subscriberMock = $this->getMockBuilder(SubscriberInterface::class)->getMock();

        $subscription = $this->createSubscription('default');
        $subscription->getStrategy()->expects($this->once())->method('removeSubscriber')->willReturn('removed');
        $subscription->getStrategy()->getStorage()->expects($this->once())->method('getSubscriber')->willReturn($subscriberMock);

        $dependencies->translator->expects($this->once())->method('trans')->willReturnCallback(function ($message) {
            return $message .'.trans';
        });
        $dependencies->subscriptionManager->method('getSubscription')->willReturn($subscription);

        $controller = $this->createInstance($dependencies);
        $request = new Request([
            'type' => 'default',
        ]);

        $response = $controller->unsubscribeAction($request);
        $this->assertEquals('{"message":"removed.trans","subscriber":{}}', $response->getContent());

        $dependencies = $this->createDependencies();
        $controller = $this->createInstance($dependencies);
        $subscription = $this->createSubscription('default');
        $subscription->getStrategy()->expects($this->never())->method('removeSubscriber')->willReturn('removed');
        $subscription->getStrategy()->getStorage()->expects($this->once())->method('getSubscriber')->willReturn(null);
        $dependencies->subscriptionManager->method('getSubscription')->willReturn($subscription);

        $this->expectException(NotFoundHttpException::class);
        $controller->unsubscribeAction($request);
    }
}

class SubscriptionControllerTestDependencies
{
    /** @var SubscriptionManager|MockObject */
    public $subscriptionManager;
    /** @var PendingSubscriberManager|MockObject */
    public $pendingManager;
    /** @var TranslatorInterface|MockObject */
    public $translator;

    /** @var Container|MockObject */
    public $container;
}

class SubscriptionTestController extends SubscriptionController
{

    protected function formIsValid($form)
    {
        return true;
    }

    protected function render(string $view, array $parameters = [], Response $response = null): Response
    {
        return new Response($view . '.rendered');
    }
}

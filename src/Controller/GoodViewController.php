<?php

namespace App\Controller;

use App\Entity\Goods;
use App\Entity\User;
use App\Entity\Transaction;
use App\Form\AddGoodType;
use App\Form\AddNewBetType;
use App\Repository\GoodsRepository;
use App\Repository\TransactionRepository;
use App\Repository\UserRepository;
use phpDocumentor\Reflection\Types\String_;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GoodViewController extends AbstractController
{
    #[Route('/good/view/{id} ',methods: ['GET','POST'] )]
    public function index(Request $request, int $id, GoodsRepository $goodsRepository, TransactionRepository $addTr,UserRepository $userRepository): Response
    {
        $user = $this->getUser();
        $good = $goodsRepository->find($id);
        $lastTransaction = $addTr->findOneBy([
            'good_id' => $id,
            'status' => '1'
        ]);
        $AllTransactions = $addTr->findBy(['good_id'=>$id]);

        $lastUser = New User();
        $msg = $good->getUser();

        $transaction = new Transaction();
        $form = $this->createForm(AddNewBetType::class, $transaction);
        $form->handleRequest($request);
        $MaxBet = 0;
        if( $lastTransaction ){
            $MaxBet = $lastTransaction->getPay();
        }


        if ($form->isSubmitted() && $form->isValid()){
            $pay = $form->get('pay');
            $BillUser = $user->getBalance();
            $VirBillUser = $user->getVirBalance();

            if (!is_null($lastTransaction)){
                $lastUser = $lastTransaction->getUserId();
                $lastPay = $lastTransaction->getPay();
            }else{
                $lastPay = 0;
            }

            if( $lastUser->getId() == $user->getId()  ){
                $error = 'Нельзя ставить более одного раза подряд';
                return $this->redirectToRoute('app_goodview_index',[
                    'id' => $id ,
                    'error' => $error,
                ] );

            }elseif( $pay->getNormData() <= $good->getCost() ){
                $error = 'Ставка должна превышать начальную стоимость';

                return $this->redirectToRoute('app_goodview_index',[
                    'id' => $id ,
                    'error' => $error,
                ] );

            }elseif( $pay->getNormData() <= $lastPay ){
                $error = 'Ставка должна превышать последнюю ставку';
                return $this->redirectToRoute('app_goodview_index',[
                    'id' => $id ,
                    'error' => $error,
                ] );

            }elseif( $pay->getNormData() > $good->getCostmax() ){
                $error = 'Ставка не должна превышать стоимость моментального выкупа';
                return $this->redirectToRoute('app_goodview_index',[
                    'id' => $id ,
                    'error' => $error,
                ] );

            }elseif( $VirBillUser < $pay->getNormData() ){
                $error = 'Недостаточно средств для ставки';
                return $this->redirectToRoute('app_goodview_index',[
                    'id' => $id ,
                    'error' => $error,
                ] );

            }else{

                if( !is_null($lastTransaction) ){
                    $lastTransaction->setStatus(false);
                    $addTr->add($lastTransaction);

                    $lastUser ->setVirBalance($lastUser ->getVirBalance() + $lastTransaction->getPay());
                }

                $transaction->setGoodId($good)->setUserId($user)->setDate()->setPay($pay->getNormData())->setStatus(true);
                $addTr->add($transaction,true);

                $user->setVirBalance($VirBillUser - $pay->getNormData());

                if($pay->getNormData() == $good->getCostmax()){
                    $good->setUser($user)->setStatus('1');

                    $goodsRepository->add($good,true);
                    $user->setBalance($BillUser - $pay->getNormData());

                }

                $userRepository->add($user,true);
                return $this->redirectToRoute('app_goodview_index',['id' => $id ] );
            }
        }
        $error =  [];
        if( !empty($_GET['error']) ){
            $error = $_GET['error'];
        }

        return $this->render('good_view/index.html.twig', [
            'good' => $good ,
            'user' => $user,
            'goodForm' => $form->createView(),
            'error' => $error,
            'lastBet'=>$MaxBet,
            'msg'=> $msg,
            'Transactions'=>$AllTransactions,
        ]);
    }

    #[Route('/good/exit/{id}',methods: ['GET','POST'] )]
    #[IsGranted('ROLE_ADMIN')]
    public function goodEnd( TransactionRepository $addTr, int $id,GoodsRepository $goodsRepository,UserRepository $userRepository): Response
    {
        $user =new User();
        $good = $goodsRepository->find($id);
        $lastTransaction = $addTr->findOneBy([
            'good_id' => $id,
            'status' => '1'
        ]);

        if( $lastTransaction ){
            $user = $lastTransaction->getUserId();
            $BillUser = $user->getBalance();

            $good->setUser($lastTransaction->getUserId());
            $good->setStatus('1');
            $goodsRepository->add($good,true);

            $user->setBalance($BillUser - $lastTransaction->getPay());
            $userRepository->add($user,true);
        }


        return $this->redirectToRoute('app_goodview_index',['id' => $id ] );

    }

    #[Route('/good/cancel/{id}',methods: ['GET','POST'] )]
    #[IsGranted('ROLE_ADMIN')]
    public function goodСancel( TransactionRepository $addTr, int $id,GoodsRepository $goodsRepository,UserRepository $userRepository ): Response
    {
        $user =new User();
        $good = $goodsRepository->find($id);
        $lastTransaction = $addTr->findOneBy([
            'good_id' => $id,
            'status' => '1'
        ]);

        if( $lastTransaction ){

            $user = $lastTransaction->getUserId();
            $VirBillUser = $user->getVirBalance();
            $user->setVirBalance($VirBillUser + $lastTransaction->getPay());
            $userRepository->add($user,true);
        }

        $good->setStatus('2');
        $goodsRepository->add($good,true);


        return $this->redirectToRoute('app_goodview_index',['id' => $id ] );
    }


}

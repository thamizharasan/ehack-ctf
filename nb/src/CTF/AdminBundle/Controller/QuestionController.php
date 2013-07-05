<?php

namespace CTF\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Filesystem\Filesystem;
use CTF\QuestBundle\Form\QuestionType;
use Symfony\Component\Finder\Finder;

class QuestionController extends Controller {
    
    public function removeAction($id, Request $request) {
        if (false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException();
        }
        
        if ($request->isXmlHttpRequest()) {
            $em = $this->getDoctrine()->getEntityManager();
            $entity = $em->getRepository('CTFQuestBundle:Question')->find($id);
            
            if ($entity) {
                $em->remove($entity);
                $em->flush();
                
                $data = array(
                    'result' => 'success',
                    'message' => 'Successfully removed question!'
                );
                
                return new Response(\json_encode($data));
            }
            
            $data = array(
                'result' => 'error',
                'message' => 'Could not remove question!'
            );
            
            return new Response(\json_encode($data));
        }
        
        return new Response('Bad Request.', 400);
    }
    
    public function attachmentsAction($stage, $level, Request $request) {
        if (false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException();
        }
        
        if ($stage && $level) {
            $salt = $this->container->getParameter('secret');
            $dir = __DIR__ . '/../../../../web/uploads/questions/' . md5($salt . '/s' . $stage . $salt . '/l' . $level . $salt);
            if (\file_exists($dir)) {
                $finder = new Finder();
                $finder->files()->in($dir)->ignoreDotFiles(true)->notName('/zip/i');

                if (\iterator_count($finder) > 0) {
                    return $this->render('CTFAdminBundle:Question:attachments.html.twig', array(
                        'list' => $finder,
                        'stage' => $stage,
                        'level' => $level
                    ));
                }
            }
        }
        
        return new Response("<hr /><i>No attachments uploaded yet</i>");
    }
    
    public function viewAttachmentAction($stage, $level, $filename, Request $request) {
        if (false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException();
        }
        
        if ($stage && $level && $filename) {
            $salt = $this->container->getParameter('secret');
            $dir = __DIR__ . '/../../../../web/uploads/questions/' . md5($salt . '/s' . $stage . $salt . '/l' . $level . $salt) . '/' . $filename;
            if (\file_exists($dir)) {
                $data = \file_get_contents($dir);
                
                $finfo = new \finfo(FILEINFO_MIME);
                
                $response = new Response($data, 200, array(
                    'Content-Type' => $finfo->file($dir),
                    'Content-Disposition' => 'inline; filename=' . $filename
                ));
                
                return $response;
            }
        }
        
        return new Response('Bad Request.', 400);
    }
    
    public function questionAction($id, $stage, Request $request) {
        if (false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException();
        }
        
        if (-1 == $id) {
            if ($request->isXmlHttpRequest() && $request->isMethod('GET')) {
                $form = $this->createForm(new QuestionType());
                
                return $this->render('CTFAdminBundle:Question:question.form.html.twig', array(
                    'form' => $form->createView()
                ));
            } else if ($request->isMethod('POST')) {
                $form = $this->createForm(new QuestionType());
                
                $form->bind($request);
                
                if ($form->isValid()) {
                    // Get Question
                    $question = $form->getData();
                    
                    // Get stage
                    $stage = $form['stage']->getData()->getId();
                    $level = $question->getLevel();
                    
                    // Check for the attachment
                    if ($form['attachment']->getData()) {
                        $file = $form->get('attachment')->getData();
                        $salt = $this->container->getParameter('secret');
                        $dir = __DIR__.'/../../../../web/uploads/questions/' . md5($salt . '/s' . $stage . $salt . '/l' . $level . $salt);
                        $extension = $file->guessExtension();
                        if (!$extension) {
                            // extension cannot be guessed
                            $extension = 'bin';
                        }
                        $newFileName = sha1($file . rand(0, 199992993)) . '.' . $extension;
                        
                        if(!\file_exists($dir)) {
                            $fs = new Filesystem();
                            
                            try {
                                $fs->mkdir($dir);
                            } catch (Exception $e) {
                                /*return new Response(\json_encode(array(
                                    'result' => 'error',
                                    'message' => 'Stage/Level already exists!'
                                )));*/
                            }
                        } else {
                            // Remove existing files
                            // before adding new ones
                            $fs = new Filesystem();
                            $finder = new Finder();
                            $finder->files()->in($dir)->ignoreDotFiles(true);
                            
                            $fs->remove($finder);
                        }
                        
                        $file->move($dir, $newFileName);
                        
                        // Unzip the file
                        $zip = new \ZipArchive();
                        $res = $zip->open($dir . DIRECTORY_SEPARATOR . $newFileName);
                        if ($res === TRUE) {
                            $zip->extractTo($dir);
                            $zip->close();
                        } else {
                            return new Response(\json_encode(array(
                                'result' => 'error',
                                'message' => 'Not a zip-file!'
                            )));
                        }
                    }
                    
                    $em = $this->getDoctrine()->getEntityManager();
                    
                    $stages = $em->getRepository('CTFQuestBundle:Stage')->find($stage);
                    $stages->addQuestion($question);
                    
                    $em->flush();
                    
                    $this->get('session')->getFlashBag()->add('success', "Successfully saved question!");
                    return $this->redirect($this->generateUrl('ctf_admin_stage'));
                } else {
                    return new Response(\json_encode(array(
                        'result' => 'error',
                        'message' => 'Some fields are invalid. Unable to save!'
                    )));
                }
            }
        } else {
            if ($request->isXmlHttpRequest() && $request->isMethod('GET')) {
                $em = $this->getDoctrine()->getEntityManager();
                $repo = $em->getRepository('CTFQuestBundle:Question');
                $form = $this->createForm(new QuestionType(), $repo->find($id));
                $srepo = $em->getRepository('CTFQuestBundle:Stage');
                $form['stage']->setData($srepo->find($stage));
                
                return $this->render('CTFAdminBundle:Question:question.edit.form.html.twig', array(
                    'form' => $form->createView(),
                    'qid' => $id,
                    'sid' => $stage
                ));
            } else if (-1 != $stage && $request->isMethod('POST')) {
                $form = $this->createForm(new QuestionType());
                $form->bind($request);
                
                if ($form->isValid()) {
                    $em = $this->getDoctrine()->getEntityManager();
                    $question = $em->getRepository('CTFQuestBundle:Question')->find($id);
                    $stage = $em->getRepository('CTFQuestBundle:Stage')->find($stage);
                    
                    // Attachment
                    if (null !== $form['attachment']->getData()) {
                        $file = $form->get('attachment')->getData();
                        $dir = __DIR__.'/../../../../web/uploads/questions' . '/s' . $stage->getId() . '/l' . $question->getLevel();
                        $extension = $file->guessExtension();
                        if (!$extension) {
                            // extension cannot be guessed
                            $extension = 'bin';
                        }
                        $newFileName = sha1($file . rand(0, 199992993)) . '.' . $extension;
                        
                        if(!\file_exists($dir)) {
                            $fs = new Filesystem();
                            
                            try {
                                $fs->mkdir($dir);
                            } catch (Exception $e) {
                                /*return new Response(\json_encode(array(
                                    'result' => 'error',
                                    'message' => 'Stage/Level already exists!'
                                )));*/
                            }
                        }
                        
                        $file->move($dir, $newFileName);
                        
                        // Unzip the file
                        $zip = new \ZipArchive();
                        $res = $zip->open($dir . DIRECTORY_SEPARATOR . $newFileName);
                        if ($res === TRUE) {
                            $zip->extractTo($dir);
                            $zip->close();
                        } else {
                            return new Response(\json_encode(array(
                                'result' => 'error',
                                'message' => 'Not a zip-file!'
                            )));
                        }
                    }
                    
                    $question->setContent($form['content']->getData());
                    $question->setTitle($form['title']->getData());
                    $question->setLevel($form['level']->getData());
                    $question->setAnswerTemplate($form['answerTemplate']->getData());
                    $question->setHints($form['hints']->getData());

                    if (true === $stage->hasQuestion($question)) {
                        if ($form['stage']->getData()->getId() != $stage->getId()) {
                            // Remove question from stage collection
                            $stage->getQuestions()->removeElement($question);
                            $em->flush();
                            
                            $newstage = $em->getRepository('CTFQuestBundle:Stage')->find($form['stage']->getData()->getId());
                            $newstage->addQuestion($question);
                            $em->merge($newstage);
                        }
                    } else {
                        $stage->addQuestion($question);
                        $em->merge($stage);
                    }
                    
                    $em->flush();
                    
                    $this->get('session')->getFlashBag()->add('success', "Saved Question!");
                    return $this->redirect($this->generateUrl('ctf_admin_stage'));
                } else {
                    $this->get('session')->getFlashBag()->add('error', "Could not save question!");
                    return $this->redirect($this->generateUrl('ctf_admin_stage'));
                }
            }
        }
        
        return new Response('Bad Request.', 400);
    }
}
<?php

namespace App\Controller;


use App\Entity\Book;
use App\Repository\AuthorRepository;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface; 
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;



class BookController extends AbstractController
{
    #[Route('/api/books', name: 'books', methods: ['GET'])]
    public function getAllBooks(BookRepository $bookRepository, SerializerInterface $serializer): JsonResponse
    {
        $bookList = $bookRepository->findAll();
        
        $jsonBookList = $serializer->serialize($bookList, 'json', ['groups' => 'getBooks']);
        return new JsonResponse($jsonBookList, Response::HTTP_OK, [], true);
    }
    

	#[Route('/api/books/{id}', name: 'detailBook', methods: ['GET'])]
public function getDetailBook(Book $book, SerializerInterface $serializer): JsonResponse
{
    $jsonBook = $serializer->serialize($book, 'json', ['groups' => 'getBooks']);
    return new JsonResponse($jsonBook, Response::HTTP_OK, [], true);
}
    
    #[Route('/api/books/{id}', name: 'deleteBook', methods: ['DELETE'])]
    public function deleteBook(Book $book, EntityManagerInterface $em): JsonResponse 
    {
        $em->remove($book);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
    

    #[Route('/api/books', name:"createBook", methods: ['POST'])]
    public function createBook(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, AuthorRepository $authorRepository): JsonResponse
    {
        // Désérialisation du contenu de la requête en objet Book
        $book = $serializer->deserialize($request->getContent(), Book::class, 'json');
    
        // Récupération de l'ensemble des données envoyées sous forme de tableau
        $content = $request->toArray();
    
        // Récupération de l'id de l'auteur. S'il n'est pas défini, alors on met -1 par défaut.
        $idAuthor = $content['idAuthor'] ?? null;
    
        // Si un ID d'auteur est fourni
        if ($idAuthor !== null) {
            // On cherche l'auteur correspondant dans la base de données
            $author = $authorRepository->find($idAuthor);
            
            // Si l'auteur n'est pas trouvé, on renvoie une erreur
            if (!$author) {
                return new JsonResponse(['error' => 'Author not found'], Response::HTTP_NOT_FOUND);
            }
    
            // On assigne l'auteur au livre
            $book->setAuthor($author);
        } else {
            return new JsonResponse(['error' => 'Author ID is required'], Response::HTTP_BAD_REQUEST);
        }
    
        // Sauvegarde du livre
        $em->persist($book);
        $em->flush();
    
        // Sérialisation du livre en réponse
        $jsonBook = $serializer->serialize($book, 'json', ['groups' => 'getBooks']);
    
        // Génération de l'URL de l'objet créé
        $location = $urlGenerator->generate('detailBook', ['id' => $book->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
    
        // Retourner la réponse avec le code HTTP 201 et l'URL du livre créé
        return new JsonResponse($jsonBook, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    #[Route('/api/books/{id}', name:"updateBook", methods:['PUT'])]

    public function updateBook(Request $request, SerializerInterface $serializer, Book $currentBook, EntityManagerInterface $em, AuthorRepository $authorRepository): JsonResponse 
    {
        $updatedBook = $serializer->deserialize($request->getContent(), 
                Book::class, 
                'json', 
                [AbstractNormalizer::OBJECT_TO_POPULATE => $currentBook]);
        $content = $request->toArray();
        $idAuthor = $content['idAuthor'] ?? -1;
        $updatedBook->setAuthor($authorRepository->find($idAuthor));
        
        $em->persist($updatedBook);
        $em->flush();
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
   }
    
}
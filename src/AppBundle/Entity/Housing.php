<?php

namespace AppBundle\Entity;

use JsonSerializable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use DateTime;

/**
 * Housing
 *
 * @ORM\Table(name="housing")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\HousingRepository")
 */
class Housing implements JsonSerializable
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var DateTime
     *
     * @Assert\DateTime()
     * @Assert\NotBlank()
     * @Assert\NotNull()
     * @ORM\Column(name="enterDate", type="datetime")
     */
    private $enterDate;

    /**
     * @var string
     *
     * @Assert\Type("string")
     * @Assert\NotBlank()
     * @Assert\NotNull()
     * @ORM\Column(name="street", type="string", length=255)
     */
    private $street;

    /**
     * @var string
     *
     * @Assert\Type("numeric")
     * @Assert\NotBlank()
     * @Assert\NotNull()
     * @ORM\Column(name="zipCode", type="string", length=10)
     */
    private $zipCode;

    /**
     * @var string
     *
     * @Assert\Type("string")
     * @Assert\NotBlank()
     * @Assert\NotNull()
     * @ORM\Column(name="city", type="string", length=255)
     */
    private $city;

    /**
     * @var string
     *
     * @Assert\Type("string")
     * @Assert\NotBlank()
     * @Assert\NotNull()
     * @ORM\Column(name="country", type="string", length=255)
     */
    private $country;

    /**
     * @ORM\OneToMany(targetEntity="Image", mappedBy="housing", cascade={"all"})
     */
    private $images;

    /**
     * @var string
     *
     * @Assert\Email()
     * @Assert\NotBlank()
     * @Assert\NotNull()
     * @ORM\Column(name="email", type="string", length=255)
     */
    private $email;

    /**
     * @var string
     * 
     * @Assert\Type("string")
     * @Assert\NotBlank()
     * @Assert\NotNull()
     * @ORM\Column(name="token", type="string", length=255)
     */
    private $token;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->images = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set enterDate
     *
     * @param \DateTime $enterDate
     *
     * @return Housing
     */
    public function setEnterDate($enterDate)
    {
        $this->enterDate = $enterDate;

        return $this;
    }

    /**
     * Get enterDate
     *
     * @return \DateTime
     */
    public function getEnterDate()
    {
        return $this->enterDate;
    }

    /**
     * Set street
     *
     * @param string $street
     *
     * @return Housing
     */
    public function setStreet($street)
    {
        $this->street = $street;

        return $this;
    }

    /**
     * Get street
     *
     * @return string
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * Set zipCode
     *
     * @param string $zipCode
     *
     * @return Housing
     */
    public function setZipCode($zipCode)
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    /**
     * Get zipCode
     *
     * @return string
     */
    public function getZipCode()
    {
        return $this->zipCode;
    }

    /**
     * Set city
     *
     * @param string $city
     *
     * @return Housing
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city
     *
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set country
     *
     * @param string $country
     *
     * @return Housing
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return Housing
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set token
     *
     * @param string $token
     *
     * @return Housing
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get token
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Add image
     *
     * @param \AppBundle\Entity\Image $image
     *
     * @return Housing
     */
    public function addImage(\AppBundle\Entity\Image $image)
    {
        $this->images[] = $image;
        $image->setHousing($this);

        return $this;
    }

    /**
     * Remove image
     *
     * @param \AppBundle\Entity\Image $image
     */
    public function removeImage(\AppBundle\Entity\Image $image)
    {
        $this->images->removeElement($image);
        $image->setHousing(null);
    }

    /**
     * Get images
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getImages()
    {
        return $this->images;
    }





    /**
     * Expose this object
     * 
     * @return array
     */
    public function jsonSerialize() {
        $obj = get_object_vars($this);
        $obj['enterDate'] = $this->enterDate->getTimestamp();
        $obj['images'] = $this->getImages()->toArray();
        return $obj;
    }
}

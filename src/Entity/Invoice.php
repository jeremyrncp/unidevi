<?php

namespace App\Entity;

use App\Repository\DevisRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DevisRepository::class)]
class Invoice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $dueAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nameCompany = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $siretCompany = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $phoneNumberCompany = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $emailCompany = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $mentionsLegales = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nameCustomer = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $addressCustomer = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cityCustomer = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $postalCodeCustomer = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $siretCustomer = null;

    #[ORM\ManyToOne(inversedBy: 'invoices')]
    private ?Customer $customer = null;

    #[ORM\Column(nullable: true)]
    private ?int $number = null;

    /**
     * @var Collection<int, Article>
     */
    #[ORM\OneToMany(targetEntity: ArticleInvoice::class, mappedBy: 'invoice', orphanRemoval: true, cascade: ["persist", "remove"])]
    private Collection $articles;

    #[ORM\ManyToOne(inversedBy: 'devis')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $style = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $sendedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $color = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $font = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $countryCompany = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $postalCodeCompany = null;

    /**
     * @var Collection<int, Upsell>
     */
    #[ORM\OneToMany(targetEntity: UpsellInvoice::class, mappedBy: 'invoice', orphanRemoval: true, cascade: ["persist", "remove"])]
    private Collection $upsells;

    #[ORM\Column(nullable: true)]
    private ?float $tvaRate = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $archivedAt = null;

    public function __construct()
    {
        $this->articles = new ArrayCollection();
        $this->upsells = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getDueAt(): ?\DateTime
    {
        return $this->dueAt;
    }

    public function setDueAt(?\DateTime $dueAt): static
    {
        $this->dueAt = $dueAt;

        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): static
    {
        $this->logo = $logo;

        return $this;
    }

    public function getNameCompany(): ?string
    {
        return $this->nameCompany;
    }

    public function setNameCompany(?string $nameCompany): static
    {
        $this->nameCompany = $nameCompany;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getSiretCompany(): ?string
    {
        return $this->siretCompany;
    }

    public function setSiretCompany(?string $siretCompany): static
    {
        $this->siretCompany = $siretCompany;

        return $this;
    }

    public function getPhoneNumberCompany(): ?string
    {
        return $this->phoneNumberCompany;
    }

    public function setPhoneNumberCompany(?string $phoneNumberCompany): static
    {
        $this->phoneNumberCompany = $phoneNumberCompany;

        return $this;
    }

    public function getEmailCompany(): ?string
    {
        return $this->emailCompany;
    }

    public function setEmailCompany(?string $emailCompany): static
    {
        $this->emailCompany = $emailCompany;

        return $this;
    }

    public function getMentionsLegales(): ?string
    {
        return $this->mentionsLegales;
    }

    public function setMentionsLegales(?string $mentionsLegales): static
    {
        $this->mentionsLegales = $mentionsLegales;

        return $this;
    }

    public function getNameCustomer(): ?string
    {
        return $this->nameCustomer;
    }

    public function setNameCustomer(?string $nameCustomer): static
    {
        $this->nameCustomer = $nameCustomer;

        return $this;
    }

    public function getAddressCustomer(): ?string
    {
        return $this->addressCustomer;
    }

    public function setAddressCustomer(?string $addressCustomer): static
    {
        $this->addressCustomer = $addressCustomer;

        return $this;
    }

    public function getCityCustomer(): ?string
    {
        return $this->cityCustomer;
    }

    public function setCityCustomer(?string $cityCustomer): static
    {
        $this->cityCustomer = $cityCustomer;

        return $this;
    }

    public function getPostalCodeCustomer(): ?string
    {
        return $this->postalCodeCustomer;
    }

    public function setPostalCodeCustomer(?string $postalCodeCustomer): static
    {
        $this->postalCodeCustomer = $postalCodeCustomer;

        return $this;
    }

    public function getSiretCustomer(): ?string
    {
        return $this->siretCustomer;
    }

    public function setSiretCustomer(?string $siretCustomer): static
    {
        $this->siretCustomer = $siretCustomer;

        return $this;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function getNumber(): ?int
    {
        return $this->number;
    }

    public function setNumber(?int $number): static
    {
        $this->number = $number;

        return $this;
    }

    /**
     * @return Collection<int, Article>
     */
    public function getArticles(): Collection
    {
        return $this->articles;
    }

    public function addArticle(Article $article): static
    {
        if (!$this->articles->contains($article)) {
            $this->articles->add($article);
            $article->setDevis($this);
        }

        return $this;
    }

    public function removeArticle(Article $article): static
    {
        if ($this->articles->removeElement($article)) {
            // set the owning side to null (unless already changed)
            if ($article->getDevis() === $this) {
                $article->setDevis(null);
            }
        }

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    public function getStyle(): ?string
    {
        return $this->style;
    }

    public function setStyle(?string $style): static
    {
        $this->style = $style;

        return $this;
    }

    public function getSendedAt(): ?\DateTime
    {
        return $this->sendedAt;
    }

    public function setSendedAt(?\DateTime $sendedAt): static
    {
        $this->sendedAt = $sendedAt;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getFont(): ?string
    {
        return $this->font;
    }

    public function setFont(?string $font): static
    {
        $this->font = $font;

        return $this;
    }

    public function getCountryCompany(): ?string
    {
        return $this->countryCompany;
    }

    public function setCountryCompany(?string $countryCompany): static
    {
        $this->countryCompany = $countryCompany;

        return $this;
    }

    public function getPostalCodeCompany(): ?string
    {
        return $this->postalCodeCompany;
    }

    public function setPostalCodeCompany(?string $postalCodeCompany): static
    {
        $this->postalCodeCompany = $postalCodeCompany;

        return $this;
    }

    /**
     * @return Collection<int, Upsell>
     */
    public function getUpsells(): Collection
    {
        return $this->upsells;
    }

    public function addUpsell(Upsell $upsell): static
    {
        if (!$this->upsells->contains($upsell)) {
            $this->upsells->add($upsell);
            $upsell->setDevis($this);
        }

        return $this;
    }

    public function removeUpsell(Upsell $upsell): static
    {
        if ($this->upsells->removeElement($upsell)) {
            // set the owning side to null (unless already changed)
            if ($upsell->getDevis() === $this) {
                $upsell->setDevis(null);
            }
        }

        return $this;
    }

    public function getTvaRate(): ?float
    {
        return $this->tvaRate;
    }

    public function setTvaRate(?float $tvaRate): static
    {
        $this->tvaRate = $tvaRate;

        return $this;
    }

    public function getArchivedAt(): ?\DateTime
    {
        return $this->archivedAt;
    }

    public function setArchivedAt(?\DateTime $archivedAt): static
    {
        $this->archivedAt = $archivedAt;

        return $this;
    }
}

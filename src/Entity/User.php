<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const PRICE_TYPE_DEVIS_GLOBAL = "global";
    public const PRICE_TYPE_DEVIS_PER_LINE = "perLine";

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $companyName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $juridical = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $postalCode = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $country = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $emailContact = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $siret = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avisGoogle = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isDisplayAvisGoogle = null;

    #[ORM\Column]
    private ?\DateTime $createdAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatar = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $phoneNumber = null;

    #[ORM\Column(nullable: true)]
    private ?int $tvaRate = null;

    #[ORM\Column(nullable: true)]
    private ?float $validityDevis = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $priceTypeDevis = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $mentionsLegalesDevis = null;

    #[ORM\Column(nullable: true)]
    private ?bool $displayFourchetteIA = null;

    #[ORM\Column(nullable: true)]
    private ?bool $proposerAutomatiquementUpsellsDevis = null;

    #[ORM\Column(nullable: true)]
    private ?bool $autoriseSuppressionGlobaleUpsellsDevis = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $styleLogo = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $styleType = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stylePolice = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $styleColor = null;

    #[ORM\OneToMany(targetEntity: Subscription::class, mappedBy: 'owner', cascade: ["remove"])]
    private Collection $subscriptions;

    /**
     * @var Collection<int, Payment>
     */
    #[ORM\OneToMany(targetEntity: Payment::class, mappedBy: 'owner', cascade: ["remove"])]
    private Collection $payments;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $trialEndedAt = null;

    /**
     * @var Collection<int, Devis>
     */
    #[ORM\OneToMany(targetEntity: Devis::class, mappedBy: 'owner', orphanRemoval: true)]
    private Collection $devis;

    /**
     * @var Collection<int, Customer>
     */
    #[ORM\OneToMany(targetEntity: Customer::class, mappedBy: 'owner')]
    private Collection $customers;

    #[ORM\Column(nullable: true)]
    private ?int $numberDevis = null;

    #[ORM\Column(nullable: true)]
    private ?int $numberFactures = null;

    /**
     * @var Collection<int, Devis>
     */
    #[ORM\OneToMany(targetEntity: Invoice::class, mappedBy: 'owner', orphanRemoval: true)]
    private Collection $invoices;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fullname = null;



    public function __construct()
    {
        $this->subscriptions = new ArrayCollection();
        $this->payments = new ArrayCollection();
        $this->devis = new ArrayCollection();
        $this->invoices = new ArrayCollection();
        $this->customers = new ArrayCollection();

    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(?string $companyName): static
    {
        $this->companyName = $companyName;

        return $this;
    }

    public function getJuridical(): ?string
    {
        return $this->juridical;
    }

    public function setJuridical(?string $juridical): static
    {
        $this->juridical = $juridical;

        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): static
    {
        $this->adresse = $adresse;

        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): static
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function getEmailContact(): ?string
    {
        return $this->emailContact;
    }

    public function setEmailContact(?string $emailContact): static
    {
        $this->emailContact = $emailContact;

        return $this;
    }

    public function getSiret(): ?string
    {
        return $this->siret;
    }

    public function setSiret(?string $siret): static
    {
        $this->siret = $siret;

        return $this;
    }

    public function getAvisGoogle(): ?string
    {
        return $this->avisGoogle;
    }

    public function setAvisGoogle(?string $avisGoogle): static
    {
        $this->avisGoogle = $avisGoogle;

        return $this;
    }

    public function isDisplayAvisGoogle(): ?bool
    {
        return $this->isDisplayAvisGoogle;
    }

    public function setIsDisplayAvisGoogle(?bool $isDisplayAvisGoogle): static
    {
        $this->isDisplayAvisGoogle = $isDisplayAvisGoogle;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): static
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): static
    {
        $this->phoneNumber = $phoneNumber;

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

    public function getValidityDevis(): ?int
    {
        return $this->validityDevis;
    }

    public function setValidityDevis(?int $validityDevis): static
    {
        $this->validityDevis = $validityDevis;

        return $this;
    }

    public function getPriceTypeDevis(): ?string
    {
        return $this->priceTypeDevis;
    }

    public function setPriceTypeDevis(?string $priceTypeDevis): static
    {
        $this->priceTypeDevis = $priceTypeDevis;

        return $this;
    }

    public function getMentionsLegalesDevis(): ?string
    {
        return $this->mentionsLegalesDevis;
    }

    public function setMentionsLegalesDevis(?string $mentionsLegalesDevis): static
    {
        $this->mentionsLegalesDevis = $mentionsLegalesDevis;

        return $this;
    }

    public function isDisplayFourchetteIA(): ?bool
    {
        return $this->displayFourchetteIA;
    }

    public function setDisplayFourchetteIA(?bool $displayFourchetteIA): static
    {
        $this->displayFourchetteIA = $displayFourchetteIA;

        return $this;
    }

    public function isProposerAutomatiquementUpsellsDevis(): ?bool
    {
        return $this->proposerAutomatiquementUpsellsDevis;
    }

    public function setProposerAutomatiquementUpsellsDevis(bool $proposerAutomatiquementUpsellsDevis): static
    {
        $this->proposerAutomatiquementUpsellsDevis = $proposerAutomatiquementUpsellsDevis;

        return $this;
    }

    public function isAutoriseSuppressionGlobaleUpsellsDevis(): ?bool
    {
        return $this->autoriseSuppressionGlobaleUpsellsDevis;
    }

    public function setAutoriseSuppressionGlobaleUpsellsDevis(?bool $autoriseSuppressionGlobaleUpsellsDevis): static
    {
        $this->autoriseSuppressionGlobaleUpsellsDevis = $autoriseSuppressionGlobaleUpsellsDevis;

        return $this;
    }

    public function getStyleLogo(): ?string
    {
        return $this->styleLogo;
    }

    public function setStyleLogo(?string $styleLogo): static
    {
        $this->styleLogo = $styleLogo;

        return $this;
    }

    public function getStyleType(): ?string
    {
        return $this->styleType;
    }

    public function setStyleType(?string $styleType): static
    {
        $this->styleType = $styleType;

        return $this;
    }

    public function getStylePolice(): ?string
    {
        return $this->stylePolice;
    }

    public function setStylePolice(?string $stylePolice): static
    {
        $this->stylePolice = $stylePolice;

        return $this;
    }

    public function getStyleColor(): ?string
    {
        return $this->styleColor;
    }

    public function setStyleColor(?string $styleColor): static
    {
        $this->styleColor = $styleColor;

        return $this;
    }


    /**
     * @return Collection<int, Subscription>
     */
    public function getSubscriptions(): Collection
    {
        return $this->subscriptions;
    }

    public function addSubscription(Subscription $subscription): static
    {
        if (!$this->subscriptions->contains($subscription)) {
            $this->subscriptions->add($subscription);
            $subscription->setOwner($this);
        }

        return $this;
    }

    public function removeSubscription(Subscription $subscription): static
    {
        if ($this->subscriptions->removeElement($subscription)) {
            // set the owning side to null (unless already changed)
            if ($subscription->getOwner() === $this) {
                $subscription->setOwner(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Payment>
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function addPayment(Payment $payment): static
    {
        if (!$this->payments->contains($payment)) {
            $this->payments->add($payment);
            $payment->setOwner($this);
        }

        return $this;
    }

    public function removePayment(Payment $payment): static
    {
        if ($this->payments->removeElement($payment)) {
            // set the owning side to null (unless already changed)
            if ($payment->getOwner() === $this) {
                $payment->setOwner(null);
            }
        }

        return $this;
    }

    public function getTrialEndedAt(): ?\DateTime
    {
        return $this->trialEndedAt;
    }

    public function setTrialEndedAt(\DateTime $trialEndedAt): static
    {
        $this->trialEndedAt = $trialEndedAt;

        return $this;
    }

    /**
     * @return Collection<int, Devis>
     */
    public function getDevis(): Collection
    {
        return $this->devis;
    }

    public function addDevi(Devis $devi): static
    {
        if (!$this->devis->contains($devi)) {
            $this->devis->add($devi);
            $devi->setOwner($this);
        }

        return $this;
    }

    public function removeDevi(Devis $devi): static
    {
        if ($this->devis->removeElement($devi)) {
            // set the owning side to null (unless already changed)
            if ($devi->getOwner() === $this) {
                $devi->setOwner(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Invoice>
     */
    public function getInvoices(): Collection
    {
        return $this->invoices;
    }

    public function addInvoice(Invoice $devi): static
    {
        if (!$this->invoices->contains($devi)) {
            $this->invoices->add($devi);
            $devi->setOwner($this);
        }

        return $this;
    }

    public function removeInvoice(Invoice $devi): static
    {
        if ($this->invoices->removeElement($devi)) {
            // set the owning side to null (unless already changed)
            if ($devi->getOwner() === $this) {
                $devi->setOwner(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Customer>
     */
    public function getCustomers(): Collection
    {
        return $this->customers;
    }

    public function addCustomer(Customer $customer): static
    {
        if (!$this->customers->contains($customer)) {
            $this->customers->add($customer);
            $customer->setOwner($this);
        }

        return $this;
    }

    public function removeCustomer(Customer $customer): static
    {
        if ($this->customers->removeElement($customer)) {
            // set the owning side to null (unless already changed)
            if ($customer->getOwner() === $this) {
                $customer->setOwner(null);
            }
        }

        return $this;
    }

    public function getNumberDevis(): ?int
    {
        return $this->numberDevis;
    }

    public function setNumberDevis(?int $numberDevis): static
    {
        $this->numberDevis = $numberDevis;

        return $this;
    }

    public function getNumberFactures(): ?int
    {
        return $this->numberFactures;
    }

    public function setNumberFactures(?int $numberFactures): static
    {
        $this->numberFactures = $numberFactures;

        return $this;
    }

    public function getFullname(): ?string
    {
        return $this->fullname;
    }

    public function setFullname(?string $fullname): static
    {
        $this->fullname = $fullname;

        return $this;
    }
}

<?php
	namespace App\Entity;

	use App\Game\ArmorRank;
	use DaybreakStudios\Utility\DoctrineEntities\EntityInterface;
	use Doctrine\Common\Collections\ArrayCollection;
	use Doctrine\Common\Collections\Collection;
	use Doctrine\Common\Collections\Selectable;
	use Doctrine\ORM\Mapping as ORM;

	/**
	 * @ORM\Entity()
	 * @ORM\Table(name="armor_sets")
	 *
	 * Class ArmorSet
	 *
	 * @package App\Entity
	 */
	class ArmorSet implements EntityInterface, LengthCachingEntityInterface {
		use EntityTrait;

		/**
		 * @ORM\Column(type="string", length=64, unique=true)
		 *
		 * @var string
		 */
		private $name;

		/**
		 * @ORM\Column(type="string", length=16)
		 *
		 * @var string
		 */
		private $rank;

		/**
		 * @ORM\OneToMany(targetEntity="App\Entity\Armor", mappedBy="armorSet")
		 *
		 * @var Collection|Selectable|Armor[]
		 */
		private $pieces;

		/**
		 * @ORM\ManyToOne(targetEntity="App\Entity\ArmorSetBonus")
		 *
		 * @var ArmorSetBonus
		 */
		private $bonus = null;

		/**
		 * @ORM\Column(type="integer", options={"unsigned": true, "default": 0})
		 *
		 * @var int
		 * @internal Used to allow API queries against "pieces.length"
		 */
		private $piecesLength = 0;

		/**
		 * ArmorSet constructor.
		 *
		 * @param string $name
		 * @param string $rank
		 * @see ArmorRank
		 */
		public function __construct(string $name, string $rank) {
			$this->name = $name;
			$this->rank = $rank;

			$this->pieces = new ArrayCollection();
		}

		/**
		 * @return string
		 */
		public function getName(): string {
			return $this->name;
		}

		/**
		 * @return string
		 */
		public function getRank(): string {
			return $this->rank;
		}

		/**
		 * @return Armor[]|Collection|Selectable
		 */
		public function getPieces() {
			return $this->pieces;
		}

		/**
		 * @return ArmorSetBonus|null
		 */
		public function getBonus(): ?ArmorSetBonus {
			return $this->bonus;
		}

		/**
		 * @param ArmorSetBonus|null $bonus
		 *
		 * @return $this
		 */
		public function setBonus(?ArmorSetBonus $bonus) {
			$this->bonus = $bonus;

			return $this;
		}

		/**
		 * {@inheritdoc}
		 */
		public function syncLengthFields(): void {
			$this->piecesLength = $this->pieces->count();
		}
	}
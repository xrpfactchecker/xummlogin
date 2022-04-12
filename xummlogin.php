<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://twitter.com/xrpfactchecker
 * @since             1.0.0
 * @package           Xummlogin
 *
 * @wordpress-plugin
 * Plugin Name:       XUMM Login
 * Plugin URI:        https://xummlogin.xrplstatus.com/
 * Description:       Enable WordPress logins using XUMM as the signing request to establish a new session. An XUMM API account is required for your API Key and API Secret.
 * Version:           1.1.0
 * Author:            XRP Fact Checker
 * Author URI:        https://twitter.com/xrpfactchecker
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       xummlogin
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'XUMMLOGIN_VERSION', '1.1.3' )0

/**
 * OpenSSL Encryption method
 */
define( 'AES_METHOD', 'aes-256-cbc' );

/**
 * Default fee for the XRPL transaction if none are provided in the settings
 */
defined('DEFAULT_FEE_TX') or define('DEFAULT_FEE_TX', '12');

/**
 * Default fee in XRP drops for the voting
 */
define( 'DEFAULT_FEE_VOTE', '1' );

/**
 * Word List default for generating username
 */
define( 'DEFAULT_WORD_LIST1', 'Accurate,Addicted,Adorable,Adventurous,Afraid,Aggressive,Alcoholic,Alert,Aloof,Ambitious,Ancient,Angry,Animated,Annoying,Anxious,Arrogant,Ashamed,Attractive,Auspicious,Awesome,Awful,Abactinal,Abandoned,Abashed,Abatable,Abatic,Abaxial,Abbatial,Abbreviated,Abducent,Abducting,Aberrant,Abeyant,Abhorrent,Abiding,Abient,Bad,Bashful,Beautiful,Belligerent,Beneficial,Best,Big,Bitter,Bizarre,Black,Blue,Boring,Brainy,Bright,Broad,Broken,Busy,Barren,Barricaded,Barytic,Basal,Basaltic,Baseborn,Based,Baseless,Basic,Bathyal,Battleful,Battlemented,Batty,Batwing,Bias,Calm,Capable,Careful,Careless,Caring,Cautious,Charming,Cheap,Cheerful,Chubby,Clean,Clever,Clumsy,Cold,Colorful,Comfortable,Concerned,Confused,Crowded,Cruel,Curious,Curly,Cute,Damaged,Dangerous,Dark,Deep,Defective,Delicate,Delicious,Depressed,Determined,Different,Dirty,Disgusting,Dry,Dusty,Daft,Daily,Dainty,Damn,Damning,Damp,Dampish,Darkling,Darned,Dauntless,Daylong,Early,Educated,Efficient,Elderly,Elegant,Embarrassed,Empty,Encouraging,Enthusiastic,Excellent,Exciting,Expensive,Fabulous,Fair,Faithful,Famous,Fancy,Fantastic,Fast,Fearful,Fearless,Fertile,Filthy,Foolish,Forgetful,Friendly,Funny,Gentle,Glamorous,Glorious,Gorgeous,Graceful,Grateful,Great,Greedy,Green,Handsome,Happy,Harsh,Healthy,Heavy,Helpful,Hilarious,Historical,Horrible,Hot,Huge,Humorous,Hungry,Ignorant,Illegal,Imaginary,Impolite,Important,Impossible,Innocent,Intelligent,Interesting,Jealous,Jolly,Juicy,Juvenile,Kind,Large,Legal,Light,Literate,Little,Lively,Lonely,Loud,Lovely,Lucky,Macho,Magical,Magnificent,Massive,Mature,Mean,Messy,Modern,Narrow,Nasty,Naughty,Nervous,New,Noisy,Nutritious,Obedient,Obese,Obnoxious,Old,Overconfident,Peaceful,Pink,Polite,Poor,Powerful,Precious,Pretty,Proud,Quick,Quiet,Rapid,Rare,Red,Remarkable,Responsible,Rich,Romantic,Royal,Rude,Scintillating,Secretive,Selfish,Serious,Sharp,Shiny,Shocking,Short,Shy,Silly,Sincere,Skinny,Slim,Slow,Small,Soft,Spicy,Spiritual,Splendid,Strong,Successful,Sweet,Talented,Tall,Tense,Terrible,Terrific,Thick,Thin,Tiny,Tactful,Tangible,Tasteful,Tasty,Teachable,Teeming,Tempean,Temperate,Tenable,Tenacious,Tender,Terrific,Thankful,Thankworthy,Therapeutic,Thorough,Thoughtful,Ugly,Unique,Untidy,Upset,Victorious,Violent,Vulgar,Warm,Weak,Wealthy,Wide,Wise,Witty,Wonderful,Worried,Young,Youthful,Zealous' );
define( 'DEFAULT_WORD_LIST2', 'Alligator,Alpaca,Anaconda,Ant,Antelope,Ape,Aphid,Armadillo,Asp,Ass,Baboon,Badger,Barracuda,Bass,Bat,Bear,Beaver,Bedbug,Bee,Beetle,Bird,Bison,Bobcat,Buffalo,Butterfly,Buzzard,Camel,Caribou,Carp,Cat,Caterpillar,Catfish,Cheetah,Chicken,Chimpanzee,Chipmunk,Cobra,Cod,Condor,Cougar,Cow,Coyote,Crab,Crane,Cricket,Crocodile,Crow,Cuckoo,Deer,Dinosaur,Dog,Dolphin,Donkey,Dove,Dragonfly,Duck,Eagle,Eel,Elephant,Emu,Falcon,Ferret,Finch,Fish,Flamingo,Flea,Fly,Fox,Frog,Goat,Goose,Gopher,Gorilla,Grasshopper,Hamster,Hare,Hawk,Hippopotamus,Horse,Hummingbird,Husky,Iguana,Impala,Kangaroo,Ladybug,Leopard,Lion,Lizard,Llama,Lobster,Mongoose,Monkey,Moose,Mosquito,Moth,Mouse,Mule,Octopus,Orca,Ostrich,Otter,Owl,Ox,Oyster,Panda,Parrot,Peacock,Pelican,Penguin,Perch,Pheasant,Pig,Pigeon,Porcupine,Quail,Rabbit,Raccoon,Rat,Rattlesnake,Raven,Rooster,Sheep,Shrew,Skunk,Snail,Snake,Spider,Tiger,Walrus,Whale,Wolf,Zebra' );
define( 'DEFAULT_WORD_LIST3', 'Abiding,Acceding,Accelerating,Accepting,Accomplishing,Achieving,Acquiring,Activating,Adapting,Adding,Addressing,Administering,Admiring,Admitting,Adopting,Advising,Affording,Agreeing,Alerting,Alighting,Allowing,Altering,Amusing,Analyzing,Announcing,Annoying,Answering,Apologizing,Appearing,Applauding,Appointing,Appraising,Appreciating,Approving,Arbitrating,Arguing,Arising,Arranging,Arresting,Arriving,Ascertaining,Asking,Assembling,Assessing,Assisting,Assuring,Attaching,Attacking,Attaining,Attempting,Attending,Avoiding,Awaking,Baking,Balancing,Banging,Banning,Barring,Bathing,Bating,Battling,Beaming,Bearing,Beating,Becoming,Begging,Beginning,Behaving,Beholding,Being,Belonging,Besetting,Betting,Biding,Binding,Biting,Bleaching,Bleeding,Blessing,Blinding,Blinking,Blotting,Blowing,Blushing,Boasting,Boiling,Bombing,Booking,Boring,Borrowing,Bouncing,Bowing,Boxing,Braking,Breaking,Breathing,Breeding,Broadcasting,Bruising,Brushing,Budgeting,Building,Bumping,Burning,Bursting,Burying,Busting,Buying,Buzzing,Calculating,Calling,Camping,Caring,Carrying,Carving,Casting,Cataloging,Catching,Causing,Challenging,Changing,Charging,Chasing,Cheating,Checking,Cheering,Chewing,Choking,Choosing,Chopping,Claiming,Clarifying,Classifying,Cleaning,Clearing,Clinging,Clipping,Closing,Clothing,Coaching,Coiling,Collecting,Coloring,Combing,Coming,Commanding,Communicating,Comparing,Competing,Compiling,Complaining,Completing,Composing,Computing,Conceiving,Concentrating,Concerning,Concluding,Conducting,Confessing,Confronting,Confusing,Connecting,Conserving,Considering,Consisting,Consolidating,Constructing,Containing,Contracting,Controlling,Converting,Coordinating,Copying,Correcting,Correlating,Costing,Coughing,Counseling,Counting,Covering,Cracking,Crashing,Crawling,Creating,Creeping,Critiquing,Crossing,Crushing,Crying,Curing,Curling,Curving,Cutting,Cycling,Damaging,Damming,Dancing,Dealing,Decaying,Deceiving,Deciding,Decorating,Defining,Delaying,Delegating,Delighting,Delivering,Demonstrating,Depending,Describing,Deserting,Deserving,Designing,Detailing,Detecting,Determining,Developing,Devising,Diagnosing,Digging,Directing,Disagreeing,Disappearing,Disapproving,Discovering,Disliking,Dispensing,Displaying,Disproving,Dissecting,Distributing,Diverting,Dividing,Diving,Doing,Doubling,Doubting,Drafting,Dragging,Draining,Dramatizing,Drawing,Dreaming,Dressing,Drinking,Dripping,Driving,Dropping,Drowning,Drumming,Drying,Dwelling,Earning,Eating,Editing,Educating,Eliminating,Embarrassing,Employing,Enacting,Encouraging,Ending,Enduring,Enforcing,Engineering,Enhancing,Enjoying,Enlisting,Ensuring,Entering,Entertaining,Establishing,Estimating,Evaluating,Examining,Exceeding,Exciting,Excusing,Executing,Exercising,Exhibiting,Existing,Expanding,Expecting,Expediting,Experimenting,Explaining,Exploding,Expressing,Extending,Extracting,Facilitating,Facing,Fading,Fancying,Fastening,Faxing,Fearing,Feeding,Feeling,Fencing,Fetching,Fighting,Filling,Filming,Finalizing,Financing,Finding,Firing,Fitting,Fixing,Flapping,Flashing,Fleeing,Flinging,Floating,Flooding,Flowering,Flowing,Flying,Folding,Following,Fooling,Forbidding,Forcing,Forecasting,Foregoing,Foreseeing,Foretelling,Forgetting,Forgiving,Forming,Formulating,Forsaking,Framing,Freezing,Frightening,Frying,Gazing,Generating,Getting,Giving,Glowing,Glueing,Going,Governing,Grabbing,Graduating,Grating,Greasing,Greeting,Grinding,Grinning,Griping,Groaning,Growing,Guaranteeing,Guarding,Guessing,Guiding,Hammering,Handling,Handwriting,Hanging,Happening,Harassing,Harming,Haunting,Heading,Healing,Heaping,Hearing,Heating,Helping,Hiding,Hitting,Holding,Hooking,Hoping,Hovering,Hugging,Humming,Hunting,Hurrying,Hurting,Hypothesizing,Identifying,Ignoring,Imagining,Implementing,Impressing,Improving,Improvising,Including,Increasing,Inducing,Informing,Initiating,Injecting,Injuring,Innovating,Inputing,Inspecting,Installing,Instituting,Instructing,Insuring,Integrating,Intending,Intensifying,Interesting,Interfering,Interlaying,Interpreting,Interrupting,Interviewing,Introducing,Inventing,Inventorying,Investigating,Irritating,Itching,Jailing,Jamming,Jogging,Joining,Joking,Judging,Juggling,Jumping,Justifying,Keeping,Killing,Kissing,Kneeling,Knitting,Knocking,Knotting,Knowing,Landing,Lasting,Laughing,Launching,Laying,Leading,Leaning,Learning,Leaving,Lecturing,Lending,Letting,Leveling,Licensing,Licking,Lifting,Lightening,Lighting,Liking,Listing,Living,Loading,Locking,Logging,Longing,Looking,Losing,Loving,Lying,Maintaining,Making,Managing,Manipulating,Manning,Manufacturing,Mapping,Marching,Marketing,Marrying,Matching,Mating,Mattering,Meaning,Measuring,Meddling,Mediating,Meeting,Melting,Memorizing,Mending,Mentoring,Milking,Mining,Misleading,Missing,Mistaking,Misunderstanding,Mixing,Moaning,Modeling,Modifying,Monitoring,Mooring,Motivating,Moving,Muddling,Mugging,Multiplying,Murdering,Nailing,Naming,Navigating,Needing,Negotiating,Nesting,Nodding,Nominating,Normalizing,Noticing,Noting,Numbering,Obeying,Objecting,Observing,Occurring,Offending,Offering,Officiating,Opening,Operating,Ordering,Organizing,Orienteering,Originating,Overcoming,Overdoing,Overdrawing,Overflowing,Overhearing,Overtaking,Overthrowing,Owing,Owning,Packing,Paddling,Painting,Parking,Participating,Parting,Passing,Pasting,Patting,Pecking,Pedaling,Peeling,Peeping,Perceiving,Performing,Permitting,Phoning,Photographing,Picking,Piloting,Pinching,Pining,Pinpointing,Planing,Planting,Pleading,Pleasing,Plugging,Poking,Polishing,Possessing,Posting,Pouring,Praising,Praying,Preaching,Preceding,Predicting,Preferring,Preparing,Prescribing,Presenting,Presetting,Presiding,Pressing,Pretending,Preventing,Pricking,Processing,Procuring,Producing,Professing,Programming,Progressing,Projecting,Promising,Promoting,Proofreading,Proposing,Protecting,Providing,Proving,Publicizing,Pulling,Pumping,Punching,Puncturing,Purchasing,Pushing,Putting,Questioning,Queueing,Quitting,Racing,Radiating,Raising,Ranking,Rating,Reaching,Reading,Realigning,Realizing,Reasoning,Receiving,Recognizing,Reconciling,Recording,Recruiting,Reducing,Referring,Reflecting,Regretting,Regulating,Reigning,Reinforcing,Rejecting,Rejoicing,Relating,Relaxing,Releasing,Relying,Remaining,Remembering,Reminding,Removing,Rendering,Reorganizing,Repairing,Repeating,Replacing,Replying,Representing,Requesting,Rescuing,Researching,Resolving,Responding,Restoring,Restructuring,Retiring,Retrieving,Returning,Reviewing,Revising,Rhyming,Riding,Ringing,Rinsing,Rising,Risking,Robing,Rocking,Rolling,Rotting,Rubbing,Ruining,Ruling,Running,Rushing,Sacking,Satisfying,Sawing,Saying,Scaring,Scattering,Scheduling,Scolding,Scorching,Scraping,Scratching,Screaming,Screwing,Scribbling,Scrubbing,Sealing,Searching,Securing,Seeing,Seeking,Selecting,Selling,Sending,Sensing,Separating,Servicing,Serving,Setting,Settling,Sewing,Shading,Shaking,Shaping,Sharing,Shearing,Shedding,Sheltering,Shining,Shivering,Shocking,Shoeing,Shooting,Shopping,Showing,Shrinking,Shrugging,Shutting,Sighing,Signaling,Signing,Simplifying,Singing,Sining,Sinking,Sipping,Siting,Sketching,Skiing,Skipping,Slapping,Slaying,Sleeping,Sliding,Slinging,Slinking,Slipping,Slitting,Slowing,Smashing,Smelling,Smiling,Smiting,Smoking,Snatching,Sneaking,Sneezing,Sniffing,Snoring,Snowing,Soaking,Solving,Soothing,Soothsaying,Sorting,Sowing,Sparing,Sparking,Sparkling,Speaking,Speeding,Spelling,Spending,Spilling,Spinning,Spiting,Splitting,Spoiling,Spotting,Spraying,Spreading,Springing,Sprouting,Squashing,Squeaking,Squealing,Squeezing,Staining,Stamping,Standing,Staring,Starting,Stealing,Stepping,Sticking,Stimulating,Stinging,Stinking,Stirring,Stitching,Stoping,Storing,Strapping,Streamlining,Strengthening,Striking,Stringing,Striping,Striving,Structuring,Stuffing,Subtracting,Sucking,Suffering,Suggesting,Summarizing,Supplying,Supposing,Surprising,Surrounding,Suspecting,Suspending,Swearing,Sweating,Swelling,Swimming,Swinging,Symbolizing,Systemizing,Tabulating,Taking,Taming,Taping,Targeting,Tasting,Teaching,Teasing,Telephoning,Telling,Tempting,Testing,Thanking,Thriving,Thrusting,Ticking,Tickling,Timing,Tipping,Tiring,Touching,Touring,Towing,Tracing,Trading,Training,Transferring,Transforming,Transporting,Trapping,Treading,Treating,Tricking,Tripping,Trotting,Troubleshooting,Trusting,Trying,Tugging,Tumbling,Tutoring,Twisting,Tying,Typing,Undergoing,Understanding,Undressing,Unfastening,Unifying,Unlocking,Unpacking,Untidying,Updating,Upgrading,Upholding,Using,Utilizing,Verbalizing,Vexing,Visiting,Wailing,Waiting,Waking,Walking,Wandering,Wanting,Warming,Warning,Washing,Watching,Watering,Wearing,Weighing,Welcoming,Wending,Wetting,Whirling,Whispering,Winding,Winking,Wiping,Wishing,Withholding,Withstanding,Wobbling,Wondering,Working,Worrying,Wrapping,Wrecking,Wrestling,Wriggling,Wringing,Writing,Yawning,Yelling,Zipping,Zooming' );
define( 'DEFAULT_WORD_LIST4', '' );

/**
 * List of possible actions and their keys
 */
define( 'ACTION_SIGNIN'   , 'signin' );
define( 'ACTION_TRUSTLINE', 'trustline' );
define( 'ACTION_PAYMENT'  , 'payment' );
define( 'ACTION_VOTING'   , 'voting' );

/**
 * TrustSet Flags
 */
define( 'TF_SET_NO_RIPPLE', 0x00020000);

/**
 * XUMM API ERRORS
 */
$xumm_api_error_codes = [
  '0'   => __('An unknown error occured.'),
  '429' => __('XUMM is receiving too many concurrent requests in a short period.'),
];

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-xummlogin-activator.php
 */
function activate_xummlogin() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-xummlogin-activator.php';
	Xummlogin_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-xummlogin-deactivator.php
 */
function deactivate_xummlogin() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-xummlogin-deactivator.php';
	Xummlogin_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_xummlogin' );
register_deactivation_hook( __FILE__, 'deactivate_xummlogin' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-xummlogin.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_xummlogin(&$messenger) {

  // Instantiate the plugin
	$plugin = new Xummlogin();

  // Instantiate the messenger 
  $messenger = new Xummlogin_Messaging();

  // Run plugin
	$plugin->run();

}

// LFG!!
run_xummlogin($xumm_messaging);
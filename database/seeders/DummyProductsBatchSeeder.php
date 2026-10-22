<?php

namespace Database\Seeders;

use App\Enums\AdCampaignStatus;
use App\Models\AdCampaign;
use App\Models\BoostPackage;
use App\Models\Industry;
use App\Models\IndustryCategory;
use App\Models\IndustryProduct;
use App\Models\IndustrySections;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DummyProductsBatchSeeder extends Seeder
{
    /**
     * Run the database seeds to populate 200+ realistic dummy products across all industry sections.
     */
    public function run(): void
    {
        // 1. Fetch available User for creator
        $creator = User::where('email', 'niaz.softvence@gmail.com')->first()
            ?? User::where('email', 'admin@gmail.com')->first()
            ?? User::first();

        if (! $creator) {
            $this->command->error('No users found to assign as product creator.');

            return;
        }

        // 2. Fetch available Industries
        $industries = Industry::all();
        $defaultIndustry = Industry::where('name', 'LIKE', '%Softvence%')->first()
            ?? Industry::first();

        // 3. Fetch Boost Packages
        $boostPackages = BoostPackage::where('status', 'active')->get();
        if ($boostPackages->isEmpty()) {
            $boostPackages = BoostPackage::all();
        }

        // 4. Curated Pool of Authentic High-Resolution Unsplash Biomedical & Neuroscience Images
        $sampleImages = [
            'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?auto=format&fit=crop&w=800&q=80',
            'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=800&q=80',
            'https://images.unsplash.com/photo-1581093458791-9f3c3900df4b?auto=format&fit=crop&w=800&q=80',
            'https://images.unsplash.com/photo-1516549655169-df83a0774514?auto=format&fit=crop&w=800&q=80',
            'https://images.unsplash.com/photo-1532938911079-1b06ac7ceec7?auto=format&fit=crop&w=800&q=80',
            'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=800&q=80',
            'https://images.unsplash.com/photo-1576091160550-2173dba999ef?auto=format&fit=crop&w=800&q=80',
            'https://images.unsplash.com/photo-1584515979956-d9f6e5d09982?auto=format&fit=crop&w=800&q=80',
            'https://images.unsplash.com/photo-1530497610245-94d3c16cda28?auto=format&fit=crop&w=800&q=80',
            'https://images.unsplash.com/photo-1559757175-5700dde675bc?auto=format&fit=crop&w=800&q=80',
            'https://images.unsplash.com/photo-1579684385127-1ef15d508118?auto=format&fit=crop&w=800&q=80',
            'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=800&q=80',
            'https://images.unsplash.com/photo-1551076805-e1869033e561?auto=format&fit=crop&w=800&q=80',
            'https://images.unsplash.com/photo-1583912267550-d44d9c95d98d?auto=format&fit=crop&w=800&q=80',
            'https://images.unsplash.com/photo-1584017911766-d451b3d0e843?auto=format&fit=crop&w=800&q=80',
            'https://images.unsplash.com/photo-1471864190281-a93a3070b6de?auto=format&fit=crop&w=800&q=80',
            'https://images.unsplash.com/photo-1507668077129-56e32842fceb?auto=format&fit=crop&w=800&q=80',
            'https://images.unsplash.com/photo-1579154204601-01588f351e67?auto=format&fit=crop&w=800&q=80',
            'https://images.unsplash.com/photo-1530026405186-ed1f139313f8?auto=format&fit=crop&w=800&q=80',
            'https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?auto=format&fit=crop&w=800&q=80',
        ];

        // 5. Section Specific Product Catalogs (15 realistic items per section)
        $catalogs = [
            // Section 1: Neuroscientific and Psychophysiological Equipment
            1 => [
                ['name' => 'CortexWave 64-Channel Wireless EEG Cap', 'desc' => 'Research-grade high-density dry electrode EEG recording system with sub-microvolt noise floor and real-time Bluetooth telemetry.', 'tags' => ['EEG', 'Wireless', 'Neuroscience']],
                ['name' => 'OptiGaze Ultra 2000Hz Infrared Eye Tracker', 'desc' => 'High-frequency binocular eye tracker designed for reading research, visual search tasks, and micro-saccadic eye movement detection.', 'tags' => ['Eye-Tracking', 'Infrared', 'Vision']],
                ['name' => 'SynapseSense Wireless Galvanic Skin Response Sensor', 'desc' => 'Exosomatic skin conductance monitor recording tonic and phasic electrodermal responses during emotional arousal experiments.', 'tags' => ['GSR', 'Autonomic', 'Physiology']],
                ['name' => 'NeuroPulse Multi-Depth Transcranial Doppler Flowmeter', 'desc' => 'Non-invasive intracranial blood flow velocimetry system detecting middle cerebral artery flow velocity changes.', 'tags' => ['Doppler', 'Hemodynamics', 'Clinical']],
                ['name' => 'BioAmp 16-Channel Physiological Signal Workstation', 'desc' => 'Versatile biomedical amplifier collecting simultaneous ECG, EMG, respiration, and photoplethysmography data.', 'tags' => ['Amplifier', 'Biosensors', 'Hardware']],
                ['name' => 'CorticalPro Subdural Micro-Electrode ECoG Grid', 'desc' => 'Platinum-iridium contact array engineered for localized direct brain surface electrocorticography recordings.', 'tags' => ['Electrode', 'ECoG', 'Invasive']],
                ['name' => 'Nexus-32 Dual-Subject Hyperscanning Hub', 'desc' => 'Synchronous two-person physiological monitoring system enabling cooperative task neural synchronization studies.', 'tags' => ['Hyperscanning', 'Social', 'Dual-User']],
                ['name' => 'SpectralEcho Functional NIRS Headband', 'desc' => 'Continuous-wave near-infrared spectroscopy device assessing prefrontal oxygenated hemoglobin dynamics.', 'tags' => ['fNIRS', 'Prefrontal', 'Optics']],
                ['name' => 'VasoScan Continuous Arterial Pulse Wave Analyzer', 'desc' => 'Non-invasive continuous blood pressure and arterial stiffness hemodynamic analysis workstation.', 'tags' => ['Hemodynamics', 'Cardio', 'Vascular']],
                ['name' => 'NeuroSonic Focus Ultrasound Neuromodulator', 'desc' => 'Low-intensity focused ultrasound stimulation apparatus targeting deep subcortical brain structures with millimeter precision.', 'tags' => ['Ultrasound', 'Neuromodulation', 'Non-Invasive']],
                ['name' => 'AxonTrack Extracellular Spike Sorter Console', 'desc' => 'Real-time multi-channel extracellular neuronal action potential waveform discrimination and spike sorting unit.', 'tags' => ['Spike-Sorting', 'Electrophysiology', 'Lab']],
                ['name' => 'PupilMetrix Dynamic Autonomic Pupillometer', 'desc' => 'Automated infrared pupillometry measuring pupillary light reflex latency and cognitive pupil dilation dynamics.', 'tags' => ['Pupillometry', 'Autonomic', 'Cognitive']],
                ['name' => 'BioStream Low-Latency Wireless EMG Telemetry Unit', 'desc' => 'Miniature wearable electromyography sensors with tri-axial inertial measurement and active shielding cables.', 'tags' => ['EMG', 'Biomechanics', 'Wireless']],
                ['name' => 'NeuroScribe Auditory Evoked Potential Screener', 'desc' => 'Dedicated clinical auditory brainstem response (ABR) screener with automated peak identification.', 'tags' => ['ABR', 'Auditory', 'Diagnostics']],
                ['name' => 'SomnoCheck Clinical Diagnostic PSG Recorder', 'desc' => 'Comprehensive 32-channel sleep diagnostic polygraph recording EEG, EOG, EMG, nasal airflow, and chest effort.', 'tags' => ['Sleep', 'Polysomnography', 'Clinical']],
            ],

            // Section 2: Psychological Assessment Instruments
            2 => [
                ['name' => 'MindMetrics Adaptive Intelligence Battery v4', 'desc' => 'Computerized adaptive cognitive assessment system evaluating fluid intelligence, working memory, and perceptual reasoning.', 'tags' => ['Intelligence', 'Cognitive', 'Psychometrics']],
                ['name' => 'NeuroDex Comprehensive Executive Function Battery', 'desc' => 'Clinician-administered battery profiling cognitive flexibility, inhibitory control, planning, and mental shifting in adults.', 'tags' => ['Executive Function', 'Neuropsych', 'Diagnostic']],
                ['name' => 'PersonaScale Five-Factor Dimensional Inventory', 'desc' => 'Standardized 240-item digital personality inventory providing validated profiles across Big Five personality facets.', 'tags' => ['Personality', 'Big-Five', 'Inventory']],
                ['name' => 'CogniTest Automated Visual Memory & Attention Matrix', 'desc' => 'Tablet-based continuous performance test screening sustained attention, response latency, and impulsivity indices.', 'tags' => ['Attention', 'ADHD', 'Memory']],
                ['name' => 'EmotiScan Computerized Affective Facial Recognition Test', 'desc' => 'Standardized behavioral test assessing micro-expression decoding and emotion attribution across diverse demographics.', 'tags' => ['Emotion', 'Social Cognition', 'Facial']],
                ['name' => 'NeuroPsych Delis Executive Function Kit Pro', 'desc' => 'Integrated battery measuring high-level executive processes including deductive reasoning and tower task spatial planning.', 'tags' => ['Planning', 'Reasoning', 'Executive']],
                ['name' => 'Wechsler Digital Spatial Processing Suite', 'desc' => 'Interactive virtual block-design and mental rotation test measuring visuospatial integration and processing speed.', 'tags' => ['Visuospatial', 'Processing Speed', 'Test']],
                ['name' => 'PsychoScreen Multi-Scale Depression & Anxiety Screener', 'desc' => 'Self-report psychometric instrument incorporating PHQ-9, GAD-7, and clinical severity progression tracking.', 'tags' => ['Mood', 'Depression', 'Clinical']],
                ['name' => 'TrailMaking Automated Digital Tactile Tablet', 'desc' => 'Stylus-based Trail Making Test Parts A & B with microsecond stroke recording and tremor kinematics extraction.', 'tags' => ['TMT', 'Kinematics', 'Speed']],
                ['name' => 'BehavioralInsight Continuous Performance Tool', 'desc' => 'Auditory and visual continuous performance test for pediatric and adult vigilance assessment.', 'tags' => ['Vigilance', 'Inhibition', 'Testing']],
                ['name' => 'Luria-Nebraska Neurodiagnostic Battery Suite', 'desc' => 'Comprehensive standardized neuropsychological diagnostic test evaluating 14 functional brain systems.', 'tags' => ['Luria', 'Neurodiagnostic', 'Standardized']],
                ['name' => 'StroopTouch Millisecond Color-Word Interference Tool', 'desc' => 'Precise voice-activated and touch-screen Stroop effect experimenter with automated interference score calculation.', 'tags' => ['Stroop', 'Interference', 'Cognition']],
                ['name' => 'PsychoMetric Pediatric Developmental Milestones Screener', 'desc' => 'Standardized developmental screening toolkit assessing fine motor, speech, language, and adaptive behavior in children.', 'tags' => ['Pediatric', 'Developmental', 'Early-Detection']],
                ['name' => 'GripDynamo Neuro-Motor Lateral Dominance System', 'desc' => 'Calibrated digital grip dynamometer evaluating motor asymmetry and manual dexterity in neurological screening.', 'tags' => ['Dexterity', 'Motor', 'Laterality']],
                ['name' => 'SymptomCheck 90-R Clinician Scoring Suite', 'desc' => 'Automated multi-dimensional symptom self-report inventory profiling 9 primary psychiatric symptom dimensions.', 'tags' => ['SCL-90', 'Psychiatric', 'Assessment']],
            ],

            // Section 3: Experimental Apparatus (Behavioral and Cognitive)
            3 => [
                ['name' => 'ChronoBox Sub-Millisecond Visual Stimulus Presenter', 'desc' => 'Precision high-refresh OLED visual display system guaranteeing zero frame jitter for tachistoscopic psychophysics.', 'tags' => ['Psychophysics', 'OLED', 'Latency']],
                ['name' => 'TachistoVision Spatial Tachistoscope Engine', 'desc' => 'Optical tachistoscope providing sub-millisecond exposure control for subliminal priming and lexical decision paradigms.', 'tags' => ['Tachistoscope', 'Priming', 'Visual']],
                ['name' => 'ResponsePad Ergonomic Millisecond Optical Keypad', 'desc' => 'Debounced optical-switch keypad for reaction time experiments with integrated millisecond timestamping hardware.', 'tags' => ['Response-Pad', 'Reaction-Time', 'Hardware']],
                ['name' => 'OlfactoMeter Automated Precision Olfactory Presenter', 'desc' => '8-channel computerized olfactometer delivering calibrated odorant pulses synchronized with respiratory phases.', 'tags' => ['Olfaction', 'Sensory', 'Chemical']],
                ['name' => 'VibroTactile Somatosensory Frequency Generator', 'desc' => 'Piezoelectric mechanical stimulator applying sinusoidal skin vibration bursts for tactile perception thresholds.', 'tags' => ['Somatosensory', 'Haptics', 'Tactile']],
                ['name' => 'AcoustiCal Precision Calibrated Tone Synthesizer', 'desc' => 'Ultra-low distortion auditory stimulus generator with sound pressure level calibration for psychoacoustic experiments.', 'tags' => ['Acoustics', 'Hearing', 'Audiometry']],
                ['name' => 'SpatialMaze Immersive VR Behavioral Rig', 'desc' => 'Head-mounted virtual reality cognitive navigation platform with omni-directional treadmill locomotion tracking.', 'tags' => ['VR', 'Spatial', 'Locomotion']],
                ['name' => 'MotorTrack Bimanual Coordination Apparatus', 'desc' => 'Dual robotic manipulanda quantifying bilateral motor control, asymmetric limb coordination, and motor learning.', 'tags' => ['Bimanual', 'Motor-Learning', 'Robotics']],
                ['name' => 'ReactionMaster Visual-Auditory Discriminator', 'desc' => 'Multi-modal stimulus integration station measuring cross-modal reaction times and temporal binding windows.', 'tags' => ['Cross-Modal', 'Binding', 'Timing']],
                ['name' => 'GripForce Continuous Isometric Load Cell Apparatus', 'desc' => 'Precision strain-gauge force transducer assessing motor fatigue, force variability, and isometric effort.', 'tags' => ['Force', 'Fatigue', 'Isometric']],
                ['name' => 'FlickerFusion Critical Frequency Analyzer', 'desc' => 'Automated CFF test console assessing cortical arousal, central nervous system fatigue, and visual processing speed.', 'tags' => ['CFF', 'Fatigue', 'Visual']],
                ['name' => 'DichoticAudio Calibrated Binaural Listening Console', 'desc' => 'Standardized dichotic listening sound station for cerebral hemisphere dominance and language lateralization studies.', 'tags' => ['Dichotic', 'Lateralization', 'Language']],
                ['name' => 'KinematicArm 3D Reaching Motion Capture Rig', 'desc' => 'Optical and electromagnetic spatial tracking system profiling arm trajectory kinematics and movement smoothness.', 'tags' => ['Kinematics', 'Trajectories', 'Reaching']],
                ['name' => 'ThermalStim Computerized Cutaneous Thermal Stimulator', 'desc' => 'Contact thermode delivering rapidly modulated warm, cold, and noxious thermal stimuli for pain psychophysics.', 'tags' => ['Thermal', 'Nociception', 'Pain']],
                ['name' => 'RotaryRod Automated Rodent Motor Activity Console', 'desc' => 'Variable-speed accelerating rotarod measuring motor coordination, balance, and cerebellar motor function.', 'tags' => ['Rotarod', 'Coordination', 'Behavioral']],
            ],

            // Section 4: General Lab and Clinical Infrastructure
            4 => [
                ['name' => 'ShieldPro Double-Shielded Faraday RF Isolation Booth', 'desc' => 'Electromagnetically shielded test booth providing 90dB attenuation across 100kHz to 10GHz for ultra-sensitive EEG.', 'tags' => ['Faraday', 'RF-Shielding', 'Acoustic']],
                ['name' => 'SilentBooth Double-Walled Sound Attenuating Suite', 'desc' => 'Acoustically isolated testing chamber with vibration-isolated floating floor conforming to ANSI S3.1 standards.', 'tags' => ['Sound-Booth', 'Acoustics', 'Isolation']],
                ['name' => 'UltraPure High-Frequency Medical Grounding Station', 'desc' => 'Ultra-low impedance isolated earth grounding distribution unit eliminating mains line interference and 50/60Hz hum.', 'tags' => ['Grounding', 'Electrical', 'Safety']],
                ['name' => 'CleanAir HEPA Positive-Pressure Biosafety Chamber', 'desc' => 'Class II biological safety cabinet providing laminar sterile airflow for clean primary neural cell culture preparation.', 'tags' => ['Biosafety', 'HEPA', 'Cell-Culture']],
                ['name' => 'BioTherm Cryogenic Tissue Sample Repository -86C', 'desc' => 'Ultra-low temperature laboratory freezer with microprocessor temperature monitoring and backup LN2 injection.', 'tags' => ['Freezer', 'Cryogenic', 'Storage']],
                ['name' => 'ZeroNoise Active AC Line Power Harmonic Filter', 'desc' => 'True online double-conversion power conditioner providing pure sinusoidal voltage to electrophysiology racks.', 'tags' => ['Power', 'Conditioner', 'Clean-Power']],
                ['name' => 'OptiBench Honeycomb Vibration-Isolated Optical Table', 'desc' => 'Pneumatically self-leveling optical workbench eliminating micro-vibrations for patch-clamp and confocal microscopy.', 'tags' => ['Vibration', 'Optical-Table', 'Isolation']],
                ['name' => 'SterilWave Automatic UV-C Biosafety Sterilizer', 'desc' => 'Medical-grade high-intensity UV-C chamber rapidly decontaminating laboratory tools and testing peripherals.', 'tags' => ['UV-C', 'Sterilizer', 'Hygiene']],
                ['name' => 'CentriPro High-Speed Refrigerated Clinical Centrifuge', 'desc' => 'High-throughput refrigerated centrifuge with swing-bucket rotor for blood fractionation and serum separation.', 'tags' => ['Centrifuge', 'Refrigerated', 'Laboratory']],
                ['name' => 'AutoTitrator Digital Analytical Laboratory Dispenser', 'desc' => 'Motorized micro-dispenser providing sub-microliter precision dosing for chemical reagent titration.', 'tags' => ['Titration', 'Dispenser', 'Chemistry']],
                ['name' => 'ErgoComfort Participant Seating & Head Positioner', 'desc' => 'Padded ergonomic examination chair equipped with adjustable chin-rest and cervical stabilizer for imaging.', 'tags' => ['Ergonomic', 'Positioning', 'Comfort']],
                ['name' => 'NanoSpectro Micro-Volume UV-Vis Spectrophotometer', 'desc' => 'Full-spectrum micro-volume spectrophotometer quantifying DNA, RNA, and protein concentrations with 1uL samples.', 'tags' => ['Spectrophotometer', 'Biochemistry', 'Quantification']],
                ['name' => 'BioSafe Liquid Nitrogen Cryo-Preserver Vault', 'desc' => 'Vacuum-insulated cryogenic storage vessel storing thousands of neural brain tissue vials at -196C.', 'tags' => ['Cryopreservation', 'Nitrogen', 'Biobank']],
                ['name' => 'SmartSensor Continuous Ambient Humidity/Temp Logger', 'desc' => 'Cloud-connected environmental monitor ensuring consistent ambient conditions inside psychoacoustic testing rooms.', 'tags' => ['Environment', 'Logging', 'Sensors']],
                ['name' => 'ElectrodeMaster Multi-Probe Ultrasonic Cleaner', 'desc' => 'Dual-frequency ultrasonic cleaning bath restoring impedance fidelity on reusable silver/silver-chloride electrodes.', 'tags' => ['Ultrasonic', 'Cleaning', 'Electrodes']],
            ],

            // Section 6: Diagnostic Imaging (Neuroscience Biotechnology)
            6 => [
                ['name' => 'ResonaScan 7T Ultra-High Field Neuro-MRI Station', 'desc' => 'State-of-the-art 7 Tesla magnetic resonance imaging console delivering sub-millimeter laminar resolution of cortical layers.', 'tags' => ['7T-MRI', 'Neuroimaging', 'High-Field']],
                ['name' => 'EchoBrain Portable Transcranial Acoustic Scanner', 'desc' => 'Point-of-care ultrasound system offering real-time acoustic parenchyma imaging and vascular perfusion analysis.', 'tags' => ['Ultrasound', 'Transcranial', 'Point-of-Care']],
                ['name' => 'FluoroGlide Radiotranslucent Surgical Stretcher', 'desc' => 'Carbon-fiber multi-positional patient stretcher compatible with biplane neurovascular fluoroscopy systems.', 'tags' => ['Fluoroscopy', 'Carbon-Fiber', 'Surgical']],
                ['name' => 'SpectraPET Combined Molecular Positron Imager', 'desc' => 'High-sensitivity brain PET scanner utilizing novel radiotracers for amyloid-beta, tau, and neuroinflammation imaging.', 'tags' => ['PET', 'Radiotracers', 'Molecular']],
                ['name' => 'MicroCT High-Resolution Bone & Neural Canal Scanner', 'desc' => 'Sub-micron resolution computed tomography system for 3D stereological modeling of cranial and spinal anatomy.', 'tags' => ['Micro-CT', 'Anatomy', '3D']],
                ['name' => 'OptiCoherence Ultra-Widefield Retinal OCT Scanner', 'desc' => 'Spectral-domain optical coherence tomography mapping retinal nerve fiber layer thickness as a biomarker for neurodegeneration.', 'tags' => ['OCT', 'Retina', 'Biomarker']],
                ['name' => 'NeuroAngio Biplane Digital Fluoroscopy Cart', 'desc' => 'Mobile fluoroscopy C-arm delivering high frame-rate contrast angiograms during endovascular stroke interventions.', 'tags' => ['Fluoroscopy', 'Angiography', 'Endovascular']],
                ['name' => 'DopplerWave Deep Transcranial Arterial Scanner', 'desc' => 'Multi-gate pulsed Doppler probe measuring blood velocities in the circle of Willis and detecting micro-emboli.', 'tags' => ['Doppler', 'Vascular', 'Circulation']],
                ['name' => 'VascularEcho Microvascular Ultrasound Doppler Hub', 'desc' => 'High-frequency microvascular Doppler scanner mapping small-vessel compliance and parenchymal capillary flow.', 'tags' => ['Microvascular', 'Perfusion', 'Doppler']],
                ['name' => 'TomographicBrain Portable Low-Field Point-of-Care MRI', 'desc' => 'Swoop-compatible low-field portable MRI operating at regular electrical outlets directly inside neuro-ICUs.', 'tags' => ['Portable-MRI', 'Low-Field', 'ICU']],
                ['name' => 'RadiantView Carbon-Fiber Fluoroscopy C-Arm Bed', 'desc' => 'Full-floating radiolucent procedure table with motorized lateral and longitudinal float for interventional radiology.', 'tags' => ['Radiolucent', 'Procedure-Bed', 'Interventional']],
                ['name' => 'HyperSpectral Neural Tissue Diagnostic Camera', 'desc' => 'Intraoperative camera distinguishing neoplastic margins from healthy cortical tissue using spectral reflectance.', 'tags' => ['Hyperspectral', 'Neurosurgery', 'Optics']],
                ['name' => 'EchoSync Mobile Intraoperative Ultrasound Station', 'desc' => 'Compact touch-screen ultrasound unit with sterilized neuro-probes for real-time surgical brain shift compensation.', 'tags' => ['Intraoperative', 'Brain-Shift', 'Ultrasound']],
                ['name' => 'FluoroPro Motorized Tilt Diagnostic Radiography Table', 'desc' => 'Fully motorized diagnostic X-ray table with integrated digital flat panel detector and anti-scatter grid.', 'tags' => ['Radiography', 'X-Ray', 'Clinical']],
                ['name' => 'NeuroGlide Contrast-Enhanced Vascular Ultrasound Unit', 'desc' => 'Microbubble contrast-enhanced harmonic imaging platform quantifying cerebral blood volume and transit time.', 'tags' => ['Contrast-Enhanced', 'Microbubbles', 'Perfusion']],
            ],

            // Section 7: Assessment Instruments (Neuroscience)
            7 => [
                ['name' => 'PolySomnoPro 64-Channel Diagnostic PSG Cart', 'desc' => 'Hospital-grade polysomnography cart with computerized sleep architecture scoring, nocturnal oximetry, and capnography.', 'tags' => ['Polysomnography', 'Sleep-Medicine', 'Diagnostic']],
                ['name' => 'IntraGuard 32-Channel Intraoperative Neuro-Monitor', 'desc' => 'Multi-modality IOM system monitoring motor-evoked, somatosensory, and brainstem auditory potentials during spinal surgery.', 'tags' => ['IONM', 'Spinal-Surgery', 'Monitoring']],
                ['name' => 'MyoTrace 8-Channel Wireless Surface EMG Console', 'desc' => 'Synchronized wireless telemetry surface electromyography system with built-in real-time RMS amplitude calculation.', 'tags' => ['EMG', 'Biomechanics', 'Wireless']],
                ['name' => 'CardioPulse High-Fidelity 12-Lead Holter ECG', 'desc' => 'Extended 7-day 12-lead digital Holter electrocardiograph detecting autonomic arrhythmias and heart rate variability.', 'tags' => ['ECG', 'Holter', 'Autonomic']],
                ['name' => 'CranialPressure Continuous Invasive ICP Sensor', 'desc' => 'Micro-transducer fiber-optic intraparenchymal catheter measuring continuous intracranial pressure and compliance.', 'tags' => ['ICP', 'Intracranial', 'Critical-Care']],
                ['name' => 'EchoDoppler Transcranial Cerebral Emboli Detector', 'desc' => 'Dual-channel transcranial Doppler monitor running automated embolus detection algorithms for stroke risk profiling.', 'tags' => ['TCD', 'Emboli', 'Stroke-Risk']],
                ['name' => 'NeuroEvoke Brainstem Auditory Response Analyzer', 'desc' => 'Comprehensive electrodiagnostic unit assessing auditory neuropathy and acoustic neuroma using high-rate click stimuli.', 'tags' => ['BAER', 'Auditory', 'Electrodiagnostic']],
                ['name' => 'ElectraGraph Clinical Nerve Conduction Console', 'desc' => 'Advanced nerve conduction velocity and repetitive nerve stimulation unit for myasthenia gravis and neuropathy diagnosis.', 'tags' => ['NCV', 'Nerve-Conduction', 'Neuromuscular']],
                ['name' => 'OptoPressure Non-Invasive Intracranial Sensor Hub', 'desc' => 'Ophthalmic artery Doppler pulsatility index estimator for non-invasive intracranial pressure screening.', 'tags' => ['Non-Invasive-ICP', 'Ophthalmic', 'Doppler']],
                ['name' => 'SyncroPlex Intensive Care Continuous EEG Monitor', 'desc' => 'Quantitative EEG console with automated seizure detection, envelope trending, and burst-suppression ratio analysis.', 'tags' => ['ICU-EEG', 'Seizure-Detection', 'Critical-Care']],
                ['name' => 'MyoGraph Clinical Concentric Needle EMG Analyzer', 'desc' => 'Low-noise concentric needle electromyograph for fasciculation detection, motor unit action potential (MUAP) analysis.', 'tags' => ['Needle-EMG', 'MUAP', 'Neurology']],
                ['name' => 'SpinalGuard Evoked Somatosensory Monitoring Kit', 'desc' => 'High-rate electrical stimulator and recording kit for continuous monitoring of dorsal column sensory pathways.', 'tags' => ['SSEP', 'Spinal', 'Monitoring']],
                ['name' => 'VascularFlow Multichannel Transcranial Doppler Pro', 'desc' => 'Hands-free robotic probe headset maintaining continuous ultrasonic acoustic window in vasospasm monitoring.', 'tags' => ['TCD-Robotic', 'Vasospasm', 'Acoustic']],
                ['name' => 'BioCardio Autonomous Arrhythmia Telemetry Hub', 'desc' => 'Cloud-connected multi-patient telemetry central station with AI-based atrial fibrillation and ventricular tachycardia alerts.', 'tags' => ['Telemetry', 'Cardiology', 'Remote-Monitoring']],
                ['name' => 'SleepMaster Home Sleep Apnea Diagnostic Polygraph', 'desc' => 'Type III ambulatory sleep apnea diagnostic device recording respiratory effort, airflow, pulse rate, and SpO2.', 'tags' => ['Sleep-Apnea', 'Home-Testing', 'Diagnostic']],
            ],

            // Section 8: Experimental Apparatus (Neuroscience)
            8 => [
                ['name' => 'OperantChamber 9-Hole Behavioral Testing Box', 'desc' => 'Computerized 5-choice and 9-hole serial reaction time task chamber evaluating impulsivity, attention, and perseveration.', 'tags' => ['Operant', 'Serial-RT', 'Behavioral']],
                ['name' => 'OmniMaze Computerized Elevated Plus Maze', 'desc' => 'Infrared backlit elevated plus maze with automated video tracking quantifying open versus closed arm exploration.', 'tags' => ['EPM', 'Anxiety', 'Preclinical']],
                ['name' => 'GazeTrack Dual-Camera Binocular Eye Monitor', 'desc' => 'Non-invasive high-precision corneal reflection eye tracking setup designed for head-fixed non-human primate experiments.', 'tags' => ['Eye-Tracking', 'Binocular', 'Primate']],
                ['name' => 'SocialColony Automated Multi-Animal Interaction Arena', 'desc' => 'RFID-assisted social interaction arena tracking individual identification, social hierarchies, and huddling behavior.', 'tags' => ['Social-Interaction', 'RFID', 'Ethology']],
                ['name' => 'WaterMaze Morris Spatial Navigation Video System', 'desc' => 'Temperature-controlled circular pool and automated hidden escape platform with real-time quadrant time analysis.', 'tags' => ['Morris-Water-Maze', 'Spatial-Memory', 'Hippocampus']],
                ['name' => 'SkinnerTouch Modular Operant Conditioning Cage', 'desc' => 'Modular behavioral test cage featuring nose-poke ports, liquid reward delivery solenoids, and cue lights.', 'tags' => ['Skinner-Box', 'Conditioning', 'Reward']],
                ['name' => 'ForcedSwim Automated Behavioral Despair Tracker', 'desc' => 'Multi-cylinder Porsolt forced swim tank setup with automated mobility/immobility duration video scoring.', 'tags' => ['Depression-Model', 'Forced-Swim', 'Preclinical']],
                ['name' => 'PupilCapture High-Frame-Rate Pupillometry Camera', 'desc' => '200fps infrared USB3 camera with dedicated machine-learning pupil ellipse fitting library.', 'tags' => ['Pupillometry', 'High-Speed', 'Vision']],
                ['name' => 'RadialArm Automated 8-Arm Foraging Maze', 'desc' => 'Automated pneumatic guillotine door 8-arm radial maze for spatial reference and working memory evaluation.', 'tags' => ['Radial-Maze', 'Working-Memory', 'Foraging']],
                ['name' => 'GroomTrack 3D Rodent Posture Recognition Rig', 'desc' => 'Multi-camera deep learning tracking rig classifying self-grooming microstructure, rearing, and stereotyped behavior.', 'tags' => ['Deep-Learning', 'Posture', 'Ethology']],
                ['name' => 'FearCondition Sound-Attenuated Shock Box', 'desc' => 'Sound-isolated chamber with calibrated auditory cue speaker and grid floor scrambler for Pavlovian fear conditioning.', 'tags' => ['Fear-Conditioning', 'Pavlovian', 'Memory']],
                ['name' => 'VisualCliff Automated Depth Perception Platform', 'desc' => 'Glass plate depth illusion apparatus with automated photobeam timing to screen visual and developmental depth perception.', 'tags' => ['Visual-Cliff', 'Developmental', 'Perception']],
                ['name' => 'OpenField Photocell Activity Tracking Box', 'desc' => 'High-resolution infrared grid box measuring locomotor activity, thigmotaxis, and center exploration.', 'tags' => ['Open-Field', 'Locomotion', 'Thigmotaxis']],
                ['name' => 'SocialReward Automated Two-Chamber Preference Rig', 'desc' => 'Dual-compartment conditioning apparatus assessing social reward and conditioned place preference.', 'tags' => ['Place-Preference', 'Social-Reward', 'Addiction']],
                ['name' => 'GripTension Digital Rodent Forelimb Dynamometer', 'desc' => 'Precision electronic force sensor testing forelimb neuromuscular grip strength and motor unit integrity.', 'tags' => ['Grip-Strength', 'Neuromuscular', 'Motor']],
            ],

            // Section 9: General Lab and Clinical Infrastructure (Neuroscience)
            9 => [
                ['name' => 'StereoPrecision Motorized Stereotaxic Frame', 'desc' => 'Motorized 3-axis stereotaxic alignment frame with 1-micron resolution digital coordinate readout and tilt manipulator.', 'tags' => ['Stereotaxic', 'Micron-Precision', 'Surgery']],
                ['name' => 'NanoInject Computerized Nanoliter Syringe Pump', 'desc' => 'Microprocessor syringe pump infusing viral vectors and fluorophores at controlled rates from 1nL/min.', 'tags' => ['Microinjection', 'Viral-Vector', 'Optogenetics']],
                ['name' => 'MicroSurg Ultra-High Torque Surgical Drill', 'desc' => 'Brushless micro-drill delivering vibration-free craniotomy drilling with foot pedal speed modulation.', 'tags' => ['Craniotomy', 'Micro-Drill', 'Surgical']],
                ['name' => 'FluoroScope Two-Photon In Vivo Laser Microscope', 'desc' => 'Two-photon imaging microscope with resonant galvanometers for deep cortical dendritic spine calcium imaging.', 'tags' => ['Two-Photon', 'In-Vivo', 'Calcium-Imaging']],
                ['name' => 'HydraManipulator 3-Axis Hydraulic Micromanipulator', 'desc' => 'Hydraulic remote micromanipulator guaranteeing drift-free intracellular patch-clamp electrode positioning.', 'tags' => ['Micromanipulator', 'Hydraulic', 'Patch-Clamp']],
                ['name' => 'VibraSlice High-Precision Brain Tissue Vibratome', 'desc' => 'High-frequency vibrating microtome preserving cellular viability in fresh acute hippocampal slice preparations.', 'tags' => ['Vibratome', 'Brain-Slices', 'Electrophysiology']],
                ['name' => 'MicroDrill Orthopedic Titanium Surgical Bur Console', 'desc' => 'Surgical bone bur console with sterile saline cooling irrigation pump for intracranial window creation.', 'tags' => ['Surgical-Bur', 'Irrigation', 'Bone-Surgery']],
                ['name' => 'OptoPatch Automated In Vivo Patch-Clamp Rig', 'desc' => 'Robotic patch-clamping setup executing automated pipette cleaning, cell finding, and gigaseal formation.', 'tags' => ['Patch-Clamp', 'Gigaseal', 'Robotic']],
                ['name' => 'ThermoController Rodent Homeothermic Blanket System', 'desc' => 'Closed-loop rectal thermistor probe and feedback heating pad preventing hypothermia during extended surgeries.', 'tags' => ['Homeothermic', 'Temperature-Control', 'Surgical']],
                ['name' => 'CryoTome Automated Clinical Cryostat Microtome', 'desc' => 'Refrigerated cryostat cutting uniform 5 to 50 micron sections of frozen neural tissue for immunohistochemistry.', 'tags' => ['Cryostat', 'Histology', 'Immunohistochemistry']],
                ['name' => 'FiberPhotometry Multi-Channel In Vivo LED Rig', 'desc' => 'Dual-wavelength 405nm/470nm fiber photometry console recording population GCaMP calcium fluorescence in awake animals.', 'tags' => ['Fiber-Photometry', 'GCaMP', 'In-Vivo']],
                ['name' => 'MicroPerfusion Automated Brain Dialysis Pump', 'desc' => 'Syringe microperfusion system driving continuous artificial cerebrospinal fluid through microdialysis probes.', 'tags' => ['Microdialysis', 'Neurochemistry', 'CSF']],
                ['name' => 'SurgicalLamp Dual-Head Shadowless LED Station', 'desc' => 'Ceiling-mounted surgical illuminator with adjustable color temperature and sterile handle controls.', 'tags' => ['Surgical-Lamp', 'Shadowless', 'Illumination']],
                ['name' => 'MagnifiScope Fluorescence Stereomicroscope', 'desc' => 'Apochromatic zoom stereomicroscope with GFP/RFP fluorescence filter sets for stereotaxic cannula placement.', 'tags' => ['Stereomicroscope', 'Fluorescence', 'Cannula']],
                ['name' => 'GlassPuller Horizontal Laser Micropipette Puller', 'desc' => 'CO2 laser-based micropipette puller fabricating quartz and borosilicate intracellular microelectrodes.', 'tags' => ['Pipette-Puller', 'Glass-Electrodes', 'Laser']],
            ],

            // Section 14: Newly Published (Psychology Publications)
            14 => [
                ['name' => 'Journal of Clinical Neuropsychology - 2026 Special Issue', 'desc' => 'Comprehensive peer-reviewed volume focusing on machine learning in cognitive screening and early dementia biomarkers.', 'tags' => ['Journal', 'Neuropsychology', 'Peer-Reviewed']],
                ['name' => 'Cognitive Behavioral Therapy for Treatment-Resistant Anxiety', 'desc' => 'Clinical handbook presenting empirical neuroimaging evidence supporting augmented exposure protocols.', 'tags' => ['Handbook', 'CBT', 'Anxiety']],
                ['name' => 'Digital Biomarkers in Major Depressive Disorder Monograph', 'desc' => 'Extensive multi-center clinical paper investigating circadian mobile actigraphy and speech acoustic alterations.', 'tags' => ['Monograph', 'Depression', 'Digital-Health']],
                ['name' => 'Longitudinal Analysis of Adolescent Brain Cognitive Development', 'desc' => 'Open-access research preprint examining structural MRI connectivity maturation across 10,000 participants.', 'tags' => ['Preprint', 'Adolescent', 'ABCD-Study']],
                ['name' => 'Handbook of Neuropsychological Rehabilitation 4th Edition', 'desc' => 'Essential clinician textbook providing evidence-based cognitive compensatory strategies following traumatic brain injury.', 'tags' => ['Textbook', 'Rehabilitation', 'TBI']],
                ['name' => 'Executive Functioning and Emotional Dysregulation Clinical Study', 'desc' => 'Peer-reviewed research exploring fronto-limbic functional connectivity deficits in adult ADHD populations.', 'tags' => ['ADHD', 'Executive-Function', 'Paper']],
                ['name' => 'Advances in Psychometric Validation of Adaptive Cognitive Tests', 'desc' => 'Methodological paper applying item response theory (IRT) to computerized neuropsychological test calibration.', 'tags' => ['Psychometrics', 'IRT', 'Methodology']],
                ['name' => 'Neuromodulation Interventions in Chronic Treatment-Resistant PTSD', 'desc' => 'Double-blind sham-controlled clinical trial investigating bilateral repetitive TMS targeting the dorsolateral prefrontal cortex.', 'tags' => ['rTMS', 'PTSD', 'Clinical-Trial']],
                ['name' => 'Sleep Fragmentation and Memory Consolidation Trial', 'desc' => 'Laboratory study demonstrating the disruptive impact of micro-arousals on hippocampal-cortical slow-wave coupling.', 'tags' => ['Sleep', 'Memory', 'Hippocampus']],
                ['name' => 'The Neurobiology of Social Attachment and Empathy Monograph', 'desc' => 'Comprehensive review compiling fMRI hyperscanning data on interpersonal synchrony during shared decision making.', 'tags' => ['Attachment', 'Empathy', 'fMRI']],
                ['name' => 'Pediatric Neuropsychology Diagnostic Manual 2026 Edition', 'desc' => 'Official clinical guidelines detailing normative test cutoff scores for specific learning disorders and autism spectrum.', 'tags' => ['Pediatric', 'Guidelines', 'Manual']],
                ['name' => 'Pharmacogenomics of Serotonin Reuptake Transporters in Anxiety', 'desc' => 'Investigative paper examining SLC6A4 gene promoter polymorphisms and antidepressant response prediction.', 'tags' => ['Genomics', 'Pharmacology', 'Serotonin']],
                ['name' => 'Computational Psychiatry: Neural Network Models of OCD', 'desc' => 'Theoretical paper constructing reinforcement learning models of orbitofrontal-striatal hyper-reactivity.', 'tags' => ['Computational', 'OCD', 'Modeling']],
                ['name' => 'Cognitive Resilience in Aging Populations: A 10-Year Meta-Analysis', 'desc' => 'Large-scale statistical meta-analysis evaluating lifestyle factors and cognitive reserve against Alzheimer pathology.', 'tags' => ['Aging', 'Resilience', 'Meta-Analysis']],
                ['name' => 'Biomarkers of Early-Onset Psychosis in Young Adults Paper', 'desc' => 'Collaborative clinical paper evaluating blood-based inflammatory cytokines and mismatch negativity ERP waveforms.', 'tags' => ['Psychosis', 'Biomarkers', 'ERP']],
            ],

            // Section 15: Newly Published (Neuroscience Publications)
            15 => [
                ['name' => 'Nature Neuroscience: Deep Brain Stimulation Connectomics', 'desc' => 'Groundbreaking paper identifying normative structural connectome pathways predicting clinical improvement in Parkinson disease.', 'tags' => ['Nature', 'DBS', 'Parkinson']],
                ['name' => 'Synaptic Plasticity Mechanisms in Learning and Memory Review', 'desc' => 'Authoritative review paper detailing AMPA receptor trafficking and long-term potentiation maintenance.', 'tags' => ['LTP', 'Synaptic', 'Plasticity']],
                ['name' => 'Single-Cell Transcriptomics of Cortical Microglia in Aging', 'desc' => 'High-throughput single-cell RNA sequencing revealing age-dependent inflammatory phenotypes across human brain regions.', 'tags' => ['scRNA-seq', 'Microglia', 'Transcriptomics']],
                ['name' => 'Connectome Mapping of Thalamocortical Projections Monograph', 'desc' => 'Diffusion spectrum imaging atlas delineating high-resolution sensory and motor thalamocortical tractography.', 'tags' => ['Connectomics', 'Thalamus', 'Atlas']],
                ['name' => 'In Vivo Imaging of Neural Stem Cell Differentiation Paper', 'desc' => 'Longitudinal two-photon imaging study tracking subventricular zone neuroblast migration and functional integration.', 'tags' => ['Stem-Cells', 'Neurogenesis', 'Imaging']],
                ['name' => 'Optogenetic Modulation of Dopaminergic Mesolimbic Circuits', 'desc' => 'Targeted viral opsin experiment demonstrating specific reward-seeking behavior reinforcement via VTA-NAc pacing.', 'tags' => ['Optogenetics', 'Dopamine', 'Mesolimbic']],
                ['name' => 'Blood-Brain Barrier Integrity in Neurodegenerative Pathology', 'desc' => 'Clinical research assessing pericyte degeneration and dynamic contrast-enhanced MRI permeability metrics.', 'tags' => ['BBB', 'Pericytes', 'Degeneration']],
                ['name' => 'Microglia-Synapse Pruning Dynamics in Neurodevelopment Trial', 'desc' => 'Laboratory investigation demonstrating complement cascade tagging and selective phagocytic elimination of weak synapses.', 'tags' => ['Complement', 'Pruning', 'Development']],
                ['name' => 'Structural MRI Analysis of Hippocampal Subfields in Epilepsy', 'desc' => 'High-resolution 7T MRI volumetric modeling identifying localized cornu ammonis CA1 sclerosis in temporal lobe epilepsy.', 'tags' => ['Hippocampus', 'Epilepsy', 'Volumetry']],
                ['name' => 'Neurovascular Coupling in Functional Brain Imaging Monograph', 'desc' => 'Comprehensive monograph on astrocytic endfeet modulation of local capillary diameter and BOLD contrast physics.', 'tags' => ['Neurovascular', 'Astrocytes', 'BOLD']],
                ['name' => 'Mechanisms of Action in Novel Rapid-Acting Antidepressants', 'desc' => 'Review detailing synaptogenesis, BDNF translation, and mTOR pathway activation induced by NMDA receptor antagonists.', 'tags' => ['Antidepressants', 'mTOR', 'Synaptogenesis']],
                ['name' => 'Astrocyte-Neuron Metabolic Cooperation in Ischemia Paper', 'desc' => 'Biochemical study detailing monocarboxylate transporter shuttle kinetics supporting neuronal ATP production.', 'tags' => ['Metabolism', 'Ischemia', 'Astrocytes']],
                ['name' => 'Spinal Cord Repair via Biomaterial Scaffolds Review', 'desc' => 'Regenerative medicine paper evaluating aligned electrospun nanofiber conduits promoting axonal regrowth across spinal lesions.', 'tags' => ['Spinal-Cord', 'Nanofiber', 'Regeneration']],
                ['name' => 'Prefrontal Cortex Microcircuitry and Working Memory Trial', 'desc' => 'In vivo multi-electrode recording demonstrating persistent reverberating attractor dynamics during delay intervals.', 'tags' => ['PFC', 'Working-Memory', 'Attractor']],
                ['name' => 'CRISPR Therapeutics for Monogenic Neurological Disorders', 'desc' => 'Clinical preprint reporting in vivo AAV-delivered base editing correcting spinocerebellar ataxia mutations in animal models.', 'tags' => ['CRISPR', 'Gene-Editing', 'Therapeutics']],
            ],

            // Section 20: Niaz Ahmed Nayeem (Psychotropics - Psychology)
            20 => [
                ['name' => 'SynaptaZen Extended-Release Serotonin Modulator', 'desc' => 'Micro-encapsulated dual serotonin 5-HT1A partial agonist and reuptake inhibitor designed for steady-state cognitive resilience.', 'tags' => ['Serotonergic', 'Microencapsulated', 'Psychotropic']],
                ['name' => 'CogniCare Dual Acetylcholinesterase Inhibitor 10mg', 'desc' => 'Targeted central acetylcholinesterase inhibitor improving cholinergic neurotransmission in mild cognitive impairment.', 'tags' => ['Cholinergic', 'Memory', 'Prescription']],
                ['name' => 'NeuroCalm Sublingual GABA-A Positive Allosteric Modulator', 'desc' => 'Rapid-dissolve sublingual formulation providing non-sedating acute anxiolytic activity with high bioavailability.', 'tags' => ['GABAergic', 'Sublingual', 'Anxiolytic']],
                ['name' => 'NeuroRest Rapid-Onset Melatonergic Agonist 5mg', 'desc' => 'Dual MT1/MT2 receptor agonist synchronizing circadian pacemaker neurons without morning residual somnolence.', 'tags' => ['Melatonin', 'Circadian', 'Sleep']],
                ['name' => 'DopaBoost Selective D2/D3 Dopamine Autoreceptor Modulator', 'desc' => 'Balanced dopaminergic compound optimizing motivational vigor, executive drive, and goal-directed task perseverance.', 'tags' => ['Dopaminergic', 'Motivation', 'Executive']],
                ['name' => 'CortexZen Fast-Acting Glutamate Receptor Modulator', 'desc' => 'Neuroprotective AMPA receptor positive allosteric modulator enhancing synaptic signal transmission and neuroplasticity.', 'tags' => ['Glutamate', 'Neuroplasticity', 'Nootropic']],
                ['name' => 'SeroBalance 20mg Delayed-Release Enteric Tablets', 'desc' => 'Enteric-coated formulation delivering targeted serotonin reuptake inhibition with reduced gastrointestinal sensitivity.', 'tags' => ['Enteric-Coated', 'Serotonin', 'Therapeutic']],
                ['name' => 'MindProtect Neurotrophic Peptide Formulation', 'desc' => 'Standardized neuropeptide complex supporting Brain-Derived Neurotrophic Factor (BDNF) expression and cellular defense.', 'tags' => ['Peptide', 'BDNF', 'Neuroprotection']],
                ['name' => 'NeuroClarity Sustained-Release Wakefulness Agent', 'desc' => 'Central alpha-1 adrenergic and orexinergic neuromodulator promoting high-fidelity vigilance during shift work.', 'tags' => ['Vigilance', 'Wakefulness', 'Focus']],
                ['name' => 'AnxioFree Rapid-Dissolve Orodispersible Tablets', 'desc' => 'Patient-friendly orally disintegrating tablet for acute situational panic and somatic anxiety stabilization.', 'tags' => ['Orodispersible', 'Panic', 'Acute-Relief']],
                ['name' => 'NeuroAdapt Standardized Rhodiola Neuro-Nutrient Matrix', 'desc' => 'Pharmaceutical-grade adaptogenic botanical extract modulating cortisol elevation under acute psychological stress.', 'tags' => ['Adaptogen', 'Cortisol', 'Botanical']],
                ['name' => 'NeuroShield Antioxidant Astaxanthin Nanocapsules', 'desc' => 'Lipid-core nanocapsules crossing the blood-brain barrier to neutralize reactive oxygen species in neural tissue.', 'tags' => ['Antioxidant', 'Nanocapsules', 'Neurovascular']],
                ['name' => 'MemoryPlus Liposomal Phosphatidylserine Formula', 'desc' => 'High-potency liposomal phospholipid supporting cell membrane fluidity and inter-neuronal synaptic signaling.', 'tags' => ['Phospholipids', 'Synaptic', 'Membrane']],
                ['name' => 'FocusMatrix Extended-Alertness Cognitive Enhancer', 'desc' => 'Synergistic formulation combining pure l-theanine with controlled-release caffeine for sustained focused attention.', 'tags' => ['Synergistic', 'Focus', 'Cognitive']],
                ['name' => 'StressRelief Endocannabinoid System Balancer', 'desc' => 'Purified non-psychoactive phytocannabinoid isolate interacting with CB1/CB2 receptors to downregulate autonomic arousal.', 'tags' => ['Cannabinoid', 'CB1', 'Stress-Relief']],
            ],

            // Section 21: niaz (Psychotropics - Neuroscience)
            21 => [
                ['name' => 'NeuroPharm Ketamine S-Enantiomer Nasal Solution', 'desc' => 'Clinical-grade esketamine hydrochloride intranasal spray for rapid-acting treatment-resistant major depression.', 'tags' => ['Esketamine', 'NMDA', 'Intranasal']],
                ['name' => 'CerebroProtect Low-Dose Micro-Nutrient Lithium Complex', 'desc' => 'Sub-therapeutic micro-dosed lithium orotate formulation promoting hippocampal neurogenesis and GSK-3B inhibition.', 'tags' => ['Lithium', 'GSK-3B', 'Neurogenesis']],
                ['name' => 'SynaptoGABA High-Affinity GABAB Receptor Agonist', 'desc' => 'Presynaptic GABAB agonist attenuating mono-synaptic and poly-synaptic reflex transmission in muscle spasticity.', 'tags' => ['GABAB', 'Spasticity', 'Motor']],
                ['name' => 'OptiSleep Dual Orexin Receptor Antagonist 15mg', 'desc' => 'Selective OX1 and OX2 receptor antagonist facilitating physiological non-REM and REM sleep transitions.', 'tags' => ['Orexin-Antagonist', 'Sleep', 'Non-REM']],
                ['name' => 'NMDA-Shield Memantine Extended-Release Capsules 28mg', 'desc' => 'Uncompetitive low-affinity NMDA receptor antagonist protecting cholinergic neurons against pathological excitotoxicity.', 'tags' => ['Memantine', 'Excitotoxicity', 'Alzheimer']],
                ['name' => 'NeuroVance Multimodal Norepinephrine Modulator', 'desc' => 'Dual norepinephrine and dopamine reuptake inhibitor improving attention and cognitive tempo in neurological fatigue.', 'tags' => ['NDRI', 'Attention', 'Cognitive-Tempo']],
                ['name' => 'CortiQuench Selective HPA Axis Cortisol Blocker', 'desc' => 'Targeted glucocorticoid receptor antagonist mitigating neurotoxic effects of chronic elevated glucocorticoids.', 'tags' => ['HPA-Axis', 'Glucocorticoid', 'Stress']],
                ['name' => 'GlucoNeuro Metabolic Brain Insulin Sensitizer', 'desc' => 'Intranasal insulin delivery enhancing cerebral glucose uptake and bioenergetic efficiency without systemic hypoglycemia.', 'tags' => ['Intranasal-Insulin', 'Metabolic', 'Bioenergetics']],
                ['name' => 'NeuroBDNF Recombinant Neuropeptide Nasal Spray', 'desc' => 'Stabilized neurotrophic factor analogue stimulating TrkB receptor dimerization and cortical neurite outgrowth.', 'tags' => ['TrkB', 'Neurotrophin', 'Neurite']],
                ['name' => 'PsychoCalm Broad-Spectrum Cannabidiol Clinical Solution', 'desc' => 'Standardized pharmaceutical CBD oral solution indicated for adjunctive therapy in refractory pediatric epilepsies.', 'tags' => ['CBD', 'Epilepsy', 'Pharma-Grade']],
                ['name' => 'SleepWave Synthetic Melatonergic Agonist 5mg', 'desc' => 'High-affinity MT1 receptor selective chronobiotic regulating sleep-wake phase delays in shift workers.', 'tags' => ['Chronobiotic', 'MT1', 'Phase-Shift']],
                ['name' => 'AlphaNeuro Central Alpha-2 Adrenergic Agonist', 'desc' => 'Selective alpha-2A agonist enhancing prefrontal cortical network connectivity and working memory fidelity.', 'tags' => ['Alpha-2A', 'Prefrontal', 'Adrenergic']],
                ['name' => 'NeuroRegen Standardized Micro-Dose Tryptamine Formulation', 'desc' => 'Pharmaceutical-grade micro-dosed indole alkaloid formulation stimulating 5-HT2A structural neuroplasticity.', 'tags' => ['5-HT2A', 'Neuroplasticity', 'Clinical-Research']],
                ['name' => 'SynapseFuel Medium-Chain Triglyceride Bio-Ketone Complex', 'desc' => 'High-purity C8 caprylic acid emulsion providing alternative ketone bodies directly to glucose-hypometabolic brains.', 'tags' => ['Ketones', 'Bioenergetics', 'Metabolic']],
                ['name' => 'GABA-Max Liposomal Microencapsulated Drops', 'desc' => 'High-bioavailability liposomal gamma-aminobutyric acid formulation reducing peripheral sympathetic tone.', 'tags' => ['Liposomal', 'GABA', 'Relaxation']],
            ],

            // Section 22: Alabama A&M University (Psychotropics - Psychology)
            22 => [
                ['name' => 'BulldogNeuro University Research Nootropic Blend', 'desc' => 'University-developed cognitive enhancement compound formulated with clinically studied bacosides and lion mane polysaccharides.', 'tags' => ['Academic', 'University-Research', 'Nootropic']],
                ['name' => 'HuntsvilleBio Clinical Neuro-Nutrient Formula', 'desc' => 'Nutritional supplement targeting mitochondrial ATP synthesis in neurons subjected to academic burnout and fatigue.', 'tags' => ['Mitochondrial', 'ATP', 'Bio-Nutrient']],
                ['name' => 'AAMU-CogniShield Botanical Neuro-Protective Extract', 'desc' => 'Standardized polyphenolic botanical extract scavenges free radicals and supports synaptic integrity.', 'tags' => ['Botanical', 'Polyphenols', 'Neuroprotective']],
                ['name' => 'AcademicFocus Sustained Attention Amino Complex', 'desc' => 'Free-form l-tyrosine and n-acetyl l-carnitine formula replenishing catecholamine neurotransmitters during examinations.', 'tags' => ['Catecholamines', 'Tyrosine', 'Academic']],
                ['name' => 'CampusCalm Stress & Cortisol Balance Solution', 'desc' => 'Calming tincture containing sensoril ashwagandha and l-theanine balancing physiological reactivity to stress.', 'tags' => ['Ashwagandha', 'Cortisol', 'Calm']],
                ['name' => 'BioRegen Plant-Derived Flavonoid Brain Supplement', 'desc' => 'Flavonoid-rich green tea and maritime pine bark extract improving cerebrovascular microcirculation and memory.', 'tags' => ['Flavonoids', 'Microcirculation', 'Memory']],
                ['name' => 'AlabamaBio Omega-3 High-DHA Neuro-Lipid Emulsion', 'desc' => 'Ultra-purified triglyceride-form docosahexaenoic acid (DHA) maintaining neuronal membrane phospholipid composition.', 'tags' => ['DHA', 'Omega-3', 'Lipids']],
                ['name' => 'DeepSleep Advanced Circadian Synchronizing Capsule', 'desc' => 'Botanical valerian root and magnesium bisglycinate complex enhancing deep slow-wave sleep restorative cycles.', 'tags' => ['Slow-Wave-Sleep', 'Magnesium', 'Botanical']],
                ['name' => 'AlphaWave L-Theanine & B-Complex Liquid Drops', 'desc' => 'Sublingual liquid promoting relaxed alertness and alpha brainwave oscillations during intensive study blocks.', 'tags' => ['Alpha-Waves', 'Theanine', 'Liquid']],
                ['name' => 'NeuroFlora Gut-Brain Axis Probiotic Formulation', 'desc' => 'Psychobiotic strain combination including Lactobacillus helveticus modulating vagal nerve signaling to the brain.', 'tags' => ['Psychobiotic', 'Microbiome', 'Gut-Brain']],
                ['name' => 'CognitiveBoost Clinical Vitamin & CoQ10 Complex', 'desc' => 'Ubiquinol and active methylated B-vitamins optimizing cerebral cellular respiration and mental stamina.', 'tags' => ['CoQ10', 'Methylated', 'Energy']],
                ['name' => 'MemoryGuard Acetyl-L-Carnitine Cognitive Fuel', 'desc' => 'High-potency ALCAR traversing the mitochondrial membrane to boost acetylcholine synthesis and memory recall.', 'tags' => ['ALCAR', 'Acetylcholine', 'Recall']],
                ['name' => 'MindRevive Bacopa Monnieri Standardized Extract', 'desc' => '55% bacosides extract clinically shown to enhance visual information processing and working memory consolidation.', 'tags' => ['Bacopa', 'Standardized', 'Memory']],
                ['name' => 'ResilienceBio Herbal Adaptogen Research Formula', 'desc' => 'Multi-adaptogen synergy optimizing hypothalamic-pituitary-adrenal axis equilibrium under chronic mental workload.', 'tags' => ['Adaptogens', 'HPA-Axis', 'Resilience']],
                ['name' => 'NeuroFuel Choline Bitartrate Cellular Booster', 'desc' => 'High-absorption bioavailable choline source supporting cellular lipid synthesis and neurotransmitter production.', 'tags' => ['Choline', 'Neurotransmitter', 'Cellular']],
            ],

            // Section 24: Niaz Ahmed Nayeem (Biotechnology - Psychology)
            24 => [
                ['name' => 'Softvence NeuroSuite Cloud BCI Platform', 'desc' => 'Enterprise cloud-native brain-computer interface platform providing real-time EEG feature extraction and artifact cleaning pipelines.', 'tags' => ['Cloud-BCI', 'Platform', 'Machine-Learning']],
                ['name' => 'BioCloud Clinical Telemetry Analytics Engine', 'desc' => 'HIPAA-compliant streaming telemetry engine parsing continuous multiparametric patient biosignals with sub-second latency.', 'tags' => ['Telemetry', 'Cloud', 'Analytics']],
                ['name' => 'MindStream AI-Powered EEG Pattern Classifier', 'desc' => 'Deep learning convolutional neural network pre-trained on 100,000 EEG hours for automated seizure and anomaly tagging.', 'tags' => ['Deep-Learning', 'AI-Classifier', 'EEG']],
                ['name' => 'CogniMetrics Web-Based Reaction Timing Suite', 'desc' => 'Browser-compatible WebAssembly psychophysics library achieving sub-millisecond keyboard and mouse event latency.', 'tags' => ['WebAssembly', 'Reaction-Time', 'Browser-Suite']],
                ['name' => 'NeuroDash Clinician Longitudinal Patient Tracker', 'desc' => 'Interactive dashboard visualizing patient cognitive recovery trajectories and medication response curves over years.', 'tags' => ['Dashboard', 'Longitudinal', 'Clinician']],
                ['name' => 'SynapseCloud High-Throughput Biosignal Storage', 'desc' => 'Time-series database engine optimized for Petabyte-scale multi-channel raw EEG, MEG, and local field potential archiving.', 'tags' => ['Time-Series', 'Storage', 'Big-Data']],
                ['name' => 'BioSync API Gateway for Medical Sensor Ingestion', 'desc' => 'Ultra-reliable zero-loss ingestion gateway connecting wearable Bluetooth and Wi-Fi physiological sensors to EHRs.', 'tags' => ['API-Gateway', 'Wearables', 'EHR']],
                ['name' => 'NeuroVision Deep-Learning Pupillometry Software', 'desc' => 'Computer vision suite calculating pupil diameter and gaze fixation metrics from standard consumer webcam feeds.', 'tags' => ['Computer-Vision', 'Webcam', 'Pupillometry']],
                ['name' => 'VoiceBiomarker Speech Pattern Acoustic Analyzer', 'desc' => 'Acoustic voice analysis SDK extracting jitter, shimmer, and formant dispersion as digital biomarkers for depression.', 'tags' => ['Acoustics', 'Voice', 'Biomarker']],
                ['name' => 'GaitAnalytics Mobile Computer Vision MoCap Tool', 'desc' => 'Smartphone video-based motion analysis software calculating stride length, cadence, and gait symmetry indices.', 'tags' => ['MoCap', 'Mobile', 'Gait']],
                ['name' => 'StressPulse Smartwatch HRV Stress Monitoring SDK', 'desc' => 'Embeddable smartwatch SDK calculating sympathetic/parasympathetic tone balance using continuous pulse plethysmography.', 'tags' => ['Smartwatch', 'HRV', 'SDK']],
                ['name' => 'SleepSync Multi-Sensor Polysomnography Dashboard', 'desc' => 'Consolidated telemetry dashboard aggregating consumer wearable metrics with in-lab clinical sleep polygraph data.', 'tags' => ['Sleep-Sync', 'Aggregation', 'Dashboard']],
                ['name' => 'NeuroSafe HIPAA-Compliant Brain Data Vault', 'desc' => 'End-to-end encrypted zero-knowledge biometric repository safeguarding raw neural telemetry and patient identification.', 'tags' => ['Encryption', 'HIPAA', 'Zero-Knowledge']],
                ['name' => 'CogniCoach Patient Neurofeedback Home Tablet App', 'desc' => 'Gamified clinical neurofeedback app allowing patients to train alpha and theta power rhythms from home tablet headsets.', 'tags' => ['Gamified', 'Neurofeedback', 'Tablet']],
                ['name' => 'BrainWave Explorer 3D Real-Time Topography Renderer', 'desc' => 'WebGL-powered 3D cortical surface renderer projecting dipole sources and scalp potential distributions in 60fps.', 'tags' => ['WebGL', '3D-Renderer', 'Topography']],
            ],

            // Section 26: dxvcvcc (Biotechnology - Neuroscience)
            26 => [
                ['name' => 'NeuroPulse Pro Clinical Biosensing Station', 'desc' => 'Integrated medical monitoring console synchronizing 64 EEG channels with arterial pulse wave and pupillometry.', 'tags' => ['NeuroPulse', 'Clinical', 'Multiparameter']],
                ['name' => 'OptoMatrix Fiber-Optic Neural Stimulation Console', 'desc' => 'Laser-diode optogenetic stimulation rack featuring sub-millisecond multi-wavelength switching for in vivo pacing.', 'tags' => ['Optogenetics', 'Fiber-Optic', 'Laser']],
                ['name' => 'BioNexus Advanced Electrophysiological Hub', 'desc' => 'Modular digital acquisition system accepting high-channel count extracellular silicon probes and micro-drives.', 'tags' => ['Silicon-Probes', 'Acquisition', 'Electrophysiology']],
                ['name' => 'SynapsePro High-Speed Data Acquisition System', 'desc' => 'PCIe-interfaced analog-to-digital converter streaming 128 channels at 50kHz per channel with zero data dropping.', 'tags' => ['High-Speed', 'PCIe', 'Hardware']],
                ['name' => 'CorticoScan Multi-Channel Local Field Potential Unit', 'desc' => 'Dedicated LFP amplifier filtering theta, gamma, and ripple oscillations with ultra-sharp active bandpass stages.', 'tags' => ['LFP', 'Oscillations', 'Gamma']],
                ['name' => 'MicroSensing Ultra-Low Noise Pre-Amplifier Box', 'desc' => 'Headstage pre-amplifier weighing only 1.2 grams engineered to record clean neural signals from freely moving subjects.', 'tags' => ['Pre-Amplifier', 'Low-Noise', 'Headstage']],
                ['name' => 'OptoLogic Laser Diode Fiber Coupling Controller', 'desc' => 'Microprocessor optical power regulator maintaining calibrated milliwatt light output at fiber tips.', 'tags' => ['Optics', 'Controller', 'Calibration']],
                ['name' => 'NeuroMatrix 128-Channel In Vivo Neural Probe', 'desc' => 'High-density micro-fabricated silicon shank with 128 recording sites spanning across entire cortical depths.', 'tags' => ['Silicon-Shank', 'High-Density', 'Probes']],
                ['name' => 'EchoSignal Real-Time Digital Signal Processor', 'desc' => 'FPGA-based DSP board computing instantaneous phase and power spectral density within 2 milliseconds.', 'tags' => ['FPGA', 'DSP', 'Real-Time']],
                ['name' => 'SpectralPulse Automated Fourier Brainwave Analyzer', 'desc' => 'Benchtop bio-analyzer running continuous fast Fourier transform and wavelet decomposition on raw electrograms.', 'tags' => ['FFT', 'Wavelet', 'Spectrogram']],
                ['name' => 'NeuroProbe Silicon Micro-machined Recording Array', 'desc' => 'Custom planar micro-electrode array featuring titanium nitride contacts for minimal tissue reaction.', 'tags' => ['TiN', 'Microelectrode', 'Planar']],
                ['name' => 'OptoTrigger Millisecond Hardware Synchronization Hub', 'desc' => 'Master clock synchronization box sending microsecond TTL triggers to cameras, stimulators, and amplifiers.', 'tags' => ['TTL', 'Synchronization', 'Clock']],
                ['name' => 'BioVolt Low-Impedance Neural Interface Cable', 'desc' => 'Gold-plated miniature ribbon cable with active electromagnetic shielding eliminating movement artifacts.', 'tags' => ['Cables', 'Shielded', 'Low-Impedance']],
                ['name' => 'CortexAmp High-Gain Biomedical Signal Rack', 'desc' => 'Modular rack-mounted signal conditioning system with variable gain from 1,000x to 100,000x for faint potentials.', 'tags' => ['High-Gain', 'Signal-Conditioning', 'Rack']],
                ['name' => 'NeuroCapture Multi-Camera Behavioral Video Encoder', 'desc' => 'Quad-camera hardware H.265 encoder locking video frames directly to electrophysiological acquisition clocks.', 'tags' => ['Video-Encoder', 'Hardware-Lock', 'Behavioral']],
            ],
        ];

        // 6. Generic Fallback Product Factory for any extra section or item
        $fallbackTitles = [
            'SynapseMaster Neural Telemetry Unit', 'CortexWave Portable EEG Interface', 'NeuroPulse High-Precision Biosensor',
            'MindMetrics Digital Cognitive Platform', 'OptiGaze High-Speed Visual Tracker', 'AeroStim Neuromodulation Stimulator',
            'CogniGlove Haptic Neuro-Rehab Device', 'BioAmp 32-Channel Signal Hub', 'NeuroTrace Clinical Electrophysiology Kit',
            'SpectraScan Multi-Modal Imaging Console', 'CelluBrain High-Resolution Bioprinter', 'NeuroLink Direct Brain Interface',
            'PolySomno Advanced Sleep Screener', 'BioStream Wearable Biosignal Sensor', 'AlphaFlow Cerebral Perfusion Monitor',
        ];

        // 7. Iterate through each section and generate products
        $allSections = IndustrySections::with('IndustryCategory')->orderBy('id')->get();
        $totalCreated = 0;
        $totalCampaigns = 0;

        foreach ($allSections as $section) {
            $categories = $section->IndustryCategory;
            if ($categories->isEmpty()) {
                // Ensure at least one category exists
                $cat = IndustryCategory::create([
                    'section_id' => $section->id,
                    'category_name' => 'General Equipment',
                ]);
                $categories = collect([$cat]);
            }

            // Products to create for this section: 15 items
            $sectionItems = $catalogs[$section->id] ?? null;
            $itemsCount = 15;

            for ($i = 0; $i < $itemsCount; $i++) {
                $category = $categories[$i % $categories->count()];

                if ($sectionItems && isset($sectionItems[$i])) {
                    $prodName = $sectionItems[$i]['name'];
                    $prodDesc = $sectionItems[$i]['desc'];
                    $prodTags = $sectionItems[$i]['tags'];
                } else {
                    $prodName = $fallbackTitles[$i % count($fallbackTitles)].' (Gen-'.($i + 1).')';
                    $prodDesc = 'Engineered for high-reliability neuroscience and clinical research with calibrated biomedical sensors and low-latency digital signal output.';
                    $prodTags = ['Research', 'Biomedical', 'Clinical'];
                }

                $selectedImage = $sampleImages[($section->id * 7 + $i) % count($sampleImages)];

                // Boosting distribution:
                // First 3 items (indices 0, 1, 2) => Sponsored ⚡
                // Next 3 items (indices 3, 4, 5) => New Release 🔥
                // Remaining 9 items (indices 6 to 14) => Regular Organic
                $isBoosted = ($i < 3);
                $isNewRelease = ($i >= 3 && $i < 6);

                $selectedIndustry = $industries->isNotEmpty()
                    ? $industries[($section->id + $i) % $industries->count()]
                    : $defaultIndustry;

                $product = IndustryProduct::create([
                    'creator_id' => $creator->id,
                    'industry_id' => $selectedIndustry ? $selectedIndustry->id : 1,
                    'network_type' => $section->network_type ?: 'psychology',
                    'industry_type' => $section->industry_type ?: 'biotechnology',
                    'section_id' => $section->id,
                    'category_id' => $category->id,
                    'product_name' => $prodName,
                    'slug' => Str::slug($prodName).'-'.Str::lower(Str::random(6)),
                    'description' => $prodDesc,
                    'product_url' => 'https://mindunite.pixelstack.cloud/products/'.Str::slug($prodName),
                    'image' => $selectedImage,
                    'tags' => $prodTags,
                    'poc_name' => $creator->first_name.' '.$creator->last_name,
                    'poc_email' => $creator->email,
                    'poc_phone' => '+8801800000000',
                    'information_confirmed' => true,
                    'status' => 'active',
                    'is_boosted' => $isBoosted,
                    'boosted_at' => $isBoosted ? now()->subHours(rand(2, 48)) : null,
                    'boost_expires_at' => $isBoosted ? now()->addDays(rand(7, 30)) : null,
                    'boost_score' => $isBoosted ? rand(100, 250) : 0,
                    'is_new_release' => $isNewRelease,
                    'new_release_expires_at' => $isNewRelease ? now()->addHours(rand(12, 48)) : null,
                    'views_count' => rand(45, 1200),
                    'unique_views_count' => rand(30, 800),
                    'likes_count' => rand(5, 180),
                    'impressions_count' => $isBoosted ? rand(600, 4500) : 0,
                    'clicks_count' => $isBoosted ? rand(30, 320) : 0,
                ]);

                $totalCreated++;

                // If boosted, create matching AdCampaign
                if ($isBoosted) {
                    $selectedPkg = $boostPackages->isNotEmpty()
                        ? $boostPackages[$i % $boostPackages->count()]
                        : null;

                    AdCampaign::create([
                        'user_id' => $creator->id,
                        'industry_id' => $selectedIndustry ? $selectedIndustry->id : 1,
                        'boost_package_id' => $selectedPkg ? $selectedPkg->id : 1,
                        'boostable_type' => IndustryProduct::class,
                        'boostable_id' => $product->id,
                        'ad_type' => 'product',
                        'network_type' => $section->network_type ?: 'psychology',
                        'placements' => ['newsfeed', 'marketplace', 'search', 'industry_product_feed'],
                        'amount_paid' => $selectedPkg ? $selectedPkg->price : 29.99,
                        'currency' => 'usd',
                        'payment_platform' => 'stripe',
                        'duration_days' => $selectedPkg ? $selectedPkg->duration_days : 14,
                        'starts_at' => now()->subHours(rand(2, 48)),
                        'ends_at' => now()->addDays(rand(7, 28)),
                        'status' => AdCampaignStatus::ACTIVE,
                        'total_impressions' => $product->impressions_count,
                        'total_clicks' => $product->clicks_count,
                    ]);

                    $totalCampaigns++;
                }
            }
        }

        $this->command->info("Successfully seeded {$totalCreated} dummy products across {$allSections->count()} sections with {$totalCampaigns} active ad campaigns!");
    }
}

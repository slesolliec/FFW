/*
 * CKFinder
 * ========
 * http://www.ckfinder.com
 * Copyright (C) 2007-2008 Frederico Caldeira Knabben (FredCK.com)
 *
 * The software, this file and its contents are subject to the CKFinder
 * License. Please read the license.txt file before using, installing, copying,
 * modifying or distribute this file or part of its contents. The contents of
 * this file is part of the Source Code of CKFinder.
 *
 * ---
 * English language file.
 */

var CKFLang =
{

Dir : 'ltr',
HelpLang : 'en',

// Date Format
//		d    : Day
//		dd   : Day (padding zero)
//		m    : Month
//		mm   : Month (padding zero)
//		yy   : Year (two digits)
//		yyyy : Year (four digits)
//		h    : Hour (12 hour clock)
//		hh   : Hour (12 hour clock, padding zero)
//		H    : Hour (24 hour clock)
//		HH   : Hour (24 hour clock, padding zero)
//		M    : Minute
//		MM   : Minute (padding zero)
//		a    : Firt char of AM/PM
//		aa   : AM/PM
DateTime : 'dd/mm/yyyy HH:MM',
DateAmPm : ['AM','PM'],

// Folders
FoldersTitle	: 'Dossiers',
FolderLoading	: 'En train de charger ...',
FolderNew		: 'Tapez le nom du nouveau dossier : ',
FolderRename	: 'Tapez le nouveau nom du dossier : ',
FolderDelete	: 'Etes-vous certain de vouloir supprimer le dossier "%1" ?',
FolderRenaming	: ' (En train de changer le nom ...)',
FolderDeleting	: ' (En train de supprimer ...)',

// Files
FileRename		: 'Tapez le nouveau nom du fichier : ',
FileRenameExt	: 'Etes-vous certain de vouloir changer l\'extention du fichier ? Cela peut le rendre inutilisable.',
FileRenaming	: 'En train de changer le nom ...',
FileDelete		: 'Etes-vous certain de vouloir supprimer le fichier "%1" ?',

// Toolbar Buttons (some used elsewhere)
Upload		: 'Télécharger',
UploadTip	: 'Télécharger un nouveau fichier',
Refresh		: 'Rafraichir',
Settings	: 'Préférences',
Help		: 'Aide',
HelpTip		: 'Aide (Anglais)',

// Context Menus
Select		: 'Sélectionner',
View		: 'Voir',
Download	: 'Télécharger',

NewSubFolder	: 'Nouveau sous-dossier',
Rename			: 'Changer le nom',
Delete			: 'Supprimer',

// Generic
OkBtn		: 'OK',
CancelBtn	: 'Annuler',
CloseBtn	: 'Fermer',

// Upload Panel
UploadTitle			: 'Télécharger un nouveau fichier',
UploadSelectLbl		: 'Choisissez le fichier à télécharger',
UploadProgressLbl	: '(Téléchargement en cours, merci de patienter ...)',
UploadBtn			: 'Télécharger le fichier sélectionné',

UploadNoFileMsg		: 'Choisissez un fichier de votre ordinateur',

// Settings Panel
SetTitle		: 'Préférences',
SetView			: 'Consultation :',
SetViewThumb	: 'Vignettes',
SetViewList		: 'Liste',
SetDisplay		: 'Affichage :',
SetDisplayName	: 'Nom du fichier',
SetDisplayDate	: 'Date',
SetDisplaySize	: 'Taille du fichier',
SetSort			: 'Tri :',
SetSortName		: 'par Nom',
SetSortDate		: 'par Date',
SetSortSize		: 'par Taille',

// Status Bar
FilesCountEmpty : '<Dossier vide>',
FilesCountOne	: '1 fichier',
FilesCountMany	: '%1 fichier',

// Connector Error Messages.
ErrorUnknown : 'Impossible de compléter la requête. (Erreur %1)',
Errors : 
{
 10 : 'Commande invalide.',
 11 : 'Le type de ressource n\'a pas été spécifié dans la requête.',
 12 : 'Le type de ressource n\'est pas valide',
102 : 'Nom de fichier ou de dossier invalide.',
103 : 'Impossible d\'effectuer cette requête en raison de restrictions d\'autorisation.',
104 : 'Impossible d\'effectuer cette requête en raison de restrictions de permissions du système de fichiers.',
105 : 'Extention du fichier invalide.',
109 : 'Requête invalide',
110 : 'Erreur inconnue.',
115 : 'Un fichier ou dossier ayant le même nom existe déjà.',
116 : 'Dossier non trouvé. Merci de rafraichir et d\'essayer à nouveau.',
117 : 'Fichier non trouvé. Merci de rafraichir la liste des fichier et d\'essayer à nouveau.',
201 : 'Un fichier ayant le même nom est déjà disponible. Le fichier téléchargé a été renommé en "%1"',
202 : 'Fichier invalide',
203 : 'Fichier invalide. Le poid du fichier est trop élevé.',
204 : 'Le fichier téléchargé est corrompu.',
205 : 'Le serveur ne dispose d\'aucun dossier temporaire pour recevoir les téléchargements.',
206 : 'Téléchargement annulé pour raisons de sécurité. Le fichier contient des données semblables à de l\'HTML.',
500 : 'Le navigateur de fichier est désactivé pour raisons de sécurité. Merci de contacter l\'administrateur du système.',
501 : 'Le support des vignettes est désactivé.'
},

// Other Error Messages.
ErrorMsg :
{
FileEmpty		: 'Le nom du ficheir ne peut être vide.',
FolderEmpty		: 'Le nom du dossier ne peut être vide.',

FileInvChar		: 'Le nom du fichier ne peut contenir l\'un de ces caractères : \n\\ / : * ? " < > |',
FolderInvChar	: 'Le nom du dossier ne peut contenir l\'un de ces caractères  : \n\\ / : * ? " < > |',

PopupBlockView	: 'Impossible d\'ouvrir le fichier dans une nouvelle fenêtre. Configurez votre navigateur pour autoriser ce site à ouvrir des fenêtres.'
}

} ;

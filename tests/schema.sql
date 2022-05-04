create table ATCMember
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('ATCMember') charset utf8 default 'ATCMember' null,
    LastEdited datetime                                            null,
    Created    datetime                                            null,
    Username   mediumtext charset utf8                             null,
    Name       mediumtext charset utf8                             null,
    Email      mediumtext charset utf8                             null,
    AltEmail   mediumtext charset utf8                             null,
    City       mediumtext charset utf8                             null,
    Country    mediumtext charset utf8                             null
)
    charset = latin1;

create index ClassName
    on ATCMember (ClassName);

create table AUCMetric
(
    ID                 int auto_increment
        primary key,
    ClassName          enum ('AUCMetric') charset utf8 default 'AUCMetric' null,
    LastEdited         datetime                                            null,
    Created            datetime                                            null,
    Identifier         varchar(50) charset utf8                            null,
    Value              varchar(50) charset utf8                            null,
    ValueDescription   varchar(50) charset utf8                            null,
    Expires            datetime                                            null,
    FoundationMemberID int                                                 null
)
    charset = latin1;

create index ClassName
    on AUCMetric (ClassName);

create index FoundationMemberID
    on AUCMetric (FoundationMemberID);

create index Identifier
    on AUCMetric (Identifier);

create table AUCMetricMissMatchError
(
    ID                int auto_increment
        primary key,
    ClassName         enum ('AUCMetricMissMatchError') charset utf8 default 'AUCMetricMissMatchError' null,
    LastEdited        datetime                                                                        null,
    Created           datetime                                                                        null,
    ServiceIdentifier varchar(50) charset utf8                                                        null,
    UserIdentifier    mediumtext charset utf8                                                         null,
    Solved            tinyint unsigned                              default '0'                       not null,
    SolvedDate        datetime                                                                        null,
    SolvedByID        int                                                                             null
)
    charset = latin1;

create index ClassName
    on AUCMetricMissMatchError (ClassName);

create index SolvedByID
    on AUCMetricMissMatchError (SolvedByID);

create table AUCMetricTranslation
(
    ID                       int auto_increment
        primary key,
    ClassName                enum ('AUCMetricTranslation') charset utf8 default 'AUCMetricTranslation' null,
    LastEdited               datetime                                                                  null,
    Created                  datetime                                                                  null,
    UserIdentifier           mediumtext charset utf8                                                   null,
    MappedFoundationMemberID int                                                                       null,
    CreatorID                int                                                                       null
)
    charset = latin1;

create index ClassName
    on AUCMetricTranslation (ClassName);

create index CreatorID
    on AUCMetricTranslation (CreatorID);

create index MappedFoundationMemberID
    on AUCMetricTranslation (MappedFoundationMemberID);

create table AbstractCalendarSyncWorkRequest
(
    ID            int auto_increment
        primary key,
    ClassName     enum ('AbstractCalendarSyncWorkRequest', 'AdminScheduleSummitActionSyncWorkRequest', 'AdminSummitEventActionSyncWorkRequest', 'AdminSummitLocationActionSyncWorkRequest', 'MemberScheduleSummitActionSyncWorkRequest', 'MemberCalendarScheduleSummitActionSyncWorkRequest', 'MemberEventScheduleSummitActionSyncWorkRequest') charset utf8 default 'AbstractCalendarSyncWorkRequest' null,
    LastEdited    datetime                                                                                                                                                                                                                                                                                                                                                                             null,
    Created       datetime                                                                                                                                                                                                                                                                                                                                                                             null,
    Type          enum ('ADD', 'REMOVE', 'UPDATE') charset utf8                                                                                                                                                                                                                                                                                              default 'ADD'                             null,
    IsProcessed   tinyint unsigned                                                                                                                                                                                                                                                                                                                           default '0'                               not null,
    ProcessedDate datetime                                                                                                                                                                                                                                                                                                                                                                             null
)
    charset = latin1;

create index ClassName
    on AbstractCalendarSyncWorkRequest (ClassName);

create table AbstractSurveyMigrationMapping
(
    ID             int auto_increment
        primary key,
    ClassName      enum ('AbstractSurveyMigrationMapping', 'NewDataModelSurveyMigrationMapping', 'OldDataModelSurveyMigrationMapping') charset utf8 default 'AbstractSurveyMigrationMapping' null,
    LastEdited     datetime                                                                                                                                                                  null,
    Created        datetime                                                                                                                                                                  null,
    TargetFieldID  int                                                                                                                                                                       null,
    TargetSurveyID int                                                                                                                                                                       null
)
    charset = latin1;

create index ClassName
    on AbstractSurveyMigrationMapping (ClassName);

create index TargetFieldID
    on AbstractSurveyMigrationMapping (TargetFieldID);

create index TargetSurveyID
    on AbstractSurveyMigrationMapping (TargetSurveyID);

create table AdminScheduleSummitActionSyncWorkRequest
(
    ID          int auto_increment
        primary key,
    CreatedByID int null
)
    charset = latin1;

create index CreatedByID
    on AdminScheduleSummitActionSyncWorkRequest (CreatedByID);

create table AdminSummitEventActionSyncWorkRequest
(
    ID            int auto_increment
        primary key,
    SummitEventID int null
)
    charset = latin1;

create index SummitEventID
    on AdminSummitEventActionSyncWorkRequest (SummitEventID);

create table AdminSummitLocationActionSyncWorkRequest
(
    ID         int auto_increment
        primary key,
    LocationID int null
)
    charset = latin1;

create index LocationID
    on AdminSummitLocationActionSyncWorkRequest (LocationID);

create table Affiliation
(
    ID             int auto_increment
        primary key,
    ClassName      enum ('Affiliation') charset utf8 default 'Affiliation' null,
    LastEdited     datetime                                                null,
    Created        datetime                                                null,
    StartDate      date                                                    null,
    EndDate        date                                                    null,
    JobTitle       mediumtext charset utf8                                 null,
    Role           mediumtext charset utf8                                 null,
    Current        tinyint unsigned                  default '0'           not null,
    MemberID       int                                                     null,
    OrganizationID int                                                     null
)
    charset = latin1;

create index ClassName
    on Affiliation (ClassName);

create index MemberID
    on Affiliation (MemberID);

create index OrganizationID
    on Affiliation (OrganizationID);

create table AffiliationUpdate
(
    ID             int auto_increment
        primary key,
    ClassName      enum ('AffiliationUpdate') charset utf8 default 'AffiliationUpdate' null,
    LastEdited     datetime                                                            null,
    Created        datetime                                                            null,
    NewAffiliation mediumtext charset utf8                                             null,
    OldAffiliation mediumtext charset utf8                                             null,
    MemberID       int                                                                 null
)
    charset = latin1;

create index ClassName
    on AffiliationUpdate (ClassName);

create index MemberID
    on AffiliationUpdate (MemberID);

create table AppDevSurvey
(
    ID                           int auto_increment
        primary key,
    ClassName                    enum ('AppDevSurvey') charset utf8 default 'AppDevSurvey' null,
    LastEdited                   datetime                                                  null,
    Created                      datetime                                                  null,
    Toolkits                     mediumtext charset utf8                                   null,
    OtherToolkits                mediumtext charset utf8                                   null,
    ProgrammingLanguages         mediumtext charset utf8                                   null,
    OtherProgrammingLanguages    mediumtext charset utf8                                   null,
    APIFormats                   mediumtext charset utf8                                   null,
    DevelopmentEnvironments      mediumtext charset utf8                                   null,
    OtherDevelopmentEnvironments mediumtext charset utf8                                   null,
    OperatingSystems             mediumtext charset utf8                                   null,
    OtherOperatingSystems        mediumtext charset utf8                                   null,
    ConfigTools                  mediumtext charset utf8                                   null,
    OtherConfigTools             mediumtext charset utf8                                   null,
    StateOfOpenStack             mediumtext charset utf8                                   null,
    DocsPriority                 mediumtext charset utf8                                   null,
    InteractionWithOtherClouds   mediumtext charset utf8                                   null,
    OtherAPIFormats              mediumtext charset utf8                                   null,
    GuestOperatingSystems        mediumtext charset utf8                                   null,
    OtherGuestOperatingSystems   mediumtext charset utf8                                   null,
    StruggleDevelopmentDeploying mediumtext charset utf8                                   null,
    OtherDocsPriority            mediumtext charset utf8                                   null,
    DeploymentSurveyID           int                                                       null,
    MemberID                     int                                                       null
)
    charset = latin1;

create index ClassName
    on AppDevSurvey (ClassName);

create index DeploymentSurveyID
    on AppDevSurvey (DeploymentSurveyID);

create index MemberID
    on AppDevSurvey (MemberID);

create table Appliance
(
    ID       int auto_increment
        primary key,
    Priority varchar(5) charset utf8 null
)
    charset = latin1;

create table ArticlePage
(
    ID     int auto_increment
        primary key,
    Date   date                    null,
    Author mediumtext charset utf8 null
)
    charset = latin1;

create table ArticlePage_Live
(
    ID     int auto_increment
        primary key,
    Date   date                    null,
    Author mediumtext charset utf8 null
)
    charset = latin1;

create table ArticlePage_versions
(
    ID       int auto_increment
        primary key,
    RecordID int default 0           not null,
    Version  int default 0           not null,
    Date     date                    null,
    Author   mediumtext charset utf8 null,
    constraint RecordID_Version
        unique (RecordID, Version)
)
    charset = latin1;

create index RecordID
    on ArticlePage_versions (RecordID);

create index Version
    on ArticlePage_versions (Version);

create table AttachmentFile
(
    ID     int auto_increment
        primary key,
    PageID int null
)
    charset = latin1;

create index PageID
    on AttachmentFile (PageID);

create table AttachmentImage
(
    ID     int auto_increment
        primary key,
    PageID int null
)
    charset = latin1;

create index PageID
    on AttachmentImage (PageID);

create table AvailabilityZone
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('AvailabilityZone') charset utf8 default 'AvailabilityZone' null,
    LastEdited datetime                                                          null,
    Created    datetime                                                          null,
    Name       varchar(50) charset utf8                                          null,
    LocationID int                                                               null,
    constraint Location_Name
        unique (LocationID, Name)
)
    charset = latin1;

create index ClassName
    on AvailabilityZone (ClassName);

create index LocationID
    on AvailabilityZone (LocationID);

create table AvailabilityZoneDraft
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('AvailabilityZoneDraft') charset utf8 default 'AvailabilityZoneDraft' null,
    LastEdited datetime                                                                    null,
    Created    datetime                                                                    null,
    Name       varchar(50) charset utf8                                                    null,
    LocationID int                                                                         null,
    constraint Location_Name
        unique (LocationID, Name)
)
    charset = latin1;

create index ClassName
    on AvailabilityZoneDraft (ClassName);

create index LocationID
    on AvailabilityZoneDraft (LocationID);

create table BatchTask
(
    ID               int auto_increment
        primary key,
    ClassName        enum ('BatchTask') charset utf8 default 'BatchTask' null,
    LastEdited       datetime                                            null,
    Created          datetime                                            null,
    Name             mediumtext charset utf8                             null,
    LastResponse     mediumtext charset utf8                             null,
    LastRecordIndex  int                             default 0           not null,
    LastResponseDate datetime                                            null,
    TotalRecords     int                             default 0           not null,
    CurrentPage      int                             default 0           not null
)
    charset = latin1;

create index ClassName
    on BatchTask (ClassName);

create table Bio
(
    ID            int auto_increment
        primary key,
    ClassName     enum ('Bio') charset utf8 default 'Bio' null,
    LastEdited    datetime                                null,
    Created       datetime                                null,
    FirstName     mediumtext charset utf8                 null,
    LastName      mediumtext charset utf8                 null,
    Email         mediumtext charset utf8                 null,
    JobTitle      mediumtext charset utf8                 null,
    Company       mediumtext charset utf8                 null,
    Bio           mediumtext charset utf8                 null,
    DisplayOnSite tinyint unsigned          default '0'   not null,
    Role          mediumtext charset utf8                 null,
    PhotoID       int                                     null,
    BioPageID     int                                     null
)
    charset = latin1;

create index BioPageID
    on Bio (BioPageID);

create index ClassName
    on Bio (ClassName);

create index PhotoID
    on Bio (PhotoID);

create table Book
(
    ID          int auto_increment
        primary key,
    ClassName   enum ('Book') charset utf8 default 'Book' null,
    LastEdited  datetime                                  null,
    Created     datetime                                  null,
    Title       varchar(255) charset utf8                 null,
    Link        varchar(255) charset utf8                 null,
    Description mediumtext charset utf8                   null,
    Slug        varchar(255) charset utf8                 null,
    CompanyID   int                                       null,
    ImageID     int                                       null
)
    charset = latin1;

create index ClassName
    on Book (ClassName);

create index CompanyID
    on Book (CompanyID);

create index ImageID
    on Book (ImageID);

create table BookAuthor
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('BookAuthor') charset utf8 default 'BookAuthor' null,
    LastEdited datetime                                              null,
    Created    datetime                                              null,
    FirstName  varchar(255) charset utf8                             null,
    LastName   varchar(255) charset utf8                             null
)
    charset = latin1;

create index ClassName
    on BookAuthor (ClassName);

create table Book_Authors
(
    ID           int auto_increment
        primary key,
    BookID       int default 0 not null,
    BookAuthorID int default 0 not null
)
    charset = latin1;

create index BookAuthorID
    on Book_Authors (BookAuthorID);

create index BookID
    on Book_Authors (BookID);

create table COALandingPage
(
    ID                      int auto_increment
        primary key,
    BannerTitle             mediumtext charset utf8      null,
    BannerText              mediumtext charset utf8      null,
    ExamDetails             mediumtext charset utf8      null,
    HandBookLink            mediumtext charset utf8      null,
    GetStartedURL           mediumtext charset utf8      null,
    GetStartedLabel         mediumtext charset utf8      null,
    GetStartedURL2          mediumtext charset utf8      null,
    GetStartedLabel2        mediumtext charset utf8      null,
    GetStartedURL3          mediumtext charset utf8      null,
    GetStartedLabel3        mediumtext charset utf8      null,
    HideFee                 tinyint unsigned default '0' not null,
    AlreadyRegisteredURL    mediumtext charset utf8      null,
    ExamCost                mediumtext charset utf8      null,
    ExamSpecialCost         mediumtext charset utf8      null,
    ExamCostSpecialOffer    mediumtext charset utf8      null,
    ExamFormat              mediumtext charset utf8      null,
    ExamIDRequirements      mediumtext charset utf8      null,
    ExamCertificationPeriod mediumtext charset utf8      null,
    ExamRetake              mediumtext charset utf8      null,
    ExamDuration            mediumtext charset utf8      null,
    ExamSystemRequirements  mediumtext charset utf8      null,
    ExamScoring             mediumtext charset utf8      null,
    ExamLanguage            mediumtext charset utf8      null,
    ExamHowLongSchedule     mediumtext charset utf8      null,
    GetStartedText          mediumtext charset utf8      null,
    HidePurchaseExam        tinyint unsigned default '0' not null,
    HideVirtualExam         tinyint unsigned default '0' not null,
    HideHowGetStarted       tinyint unsigned default '0' not null,
    HeroImageID             int                          null
)
    charset = latin1;

create index HeroImageID
    on COALandingPage (HeroImageID);

create table COALandingPage_Live
(
    ID                      int auto_increment
        primary key,
    BannerTitle             mediumtext charset utf8      null,
    BannerText              mediumtext charset utf8      null,
    ExamDetails             mediumtext charset utf8      null,
    HandBookLink            mediumtext charset utf8      null,
    GetStartedURL           mediumtext charset utf8      null,
    GetStartedLabel         mediumtext charset utf8      null,
    GetStartedURL2          mediumtext charset utf8      null,
    GetStartedLabel2        mediumtext charset utf8      null,
    GetStartedURL3          mediumtext charset utf8      null,
    GetStartedLabel3        mediumtext charset utf8      null,
    HideFee                 tinyint unsigned default '0' not null,
    AlreadyRegisteredURL    mediumtext charset utf8      null,
    ExamCost                mediumtext charset utf8      null,
    ExamSpecialCost         mediumtext charset utf8      null,
    ExamCostSpecialOffer    mediumtext charset utf8      null,
    ExamFormat              mediumtext charset utf8      null,
    ExamIDRequirements      mediumtext charset utf8      null,
    ExamCertificationPeriod mediumtext charset utf8      null,
    ExamRetake              mediumtext charset utf8      null,
    ExamDuration            mediumtext charset utf8      null,
    ExamSystemRequirements  mediumtext charset utf8      null,
    ExamScoring             mediumtext charset utf8      null,
    ExamLanguage            mediumtext charset utf8      null,
    ExamHowLongSchedule     mediumtext charset utf8      null,
    GetStartedText          mediumtext charset utf8      null,
    HidePurchaseExam        tinyint unsigned default '0' not null,
    HideVirtualExam         tinyint unsigned default '0' not null,
    HideHowGetStarted       tinyint unsigned default '0' not null,
    HeroImageID             int                          null
)
    charset = latin1;

create index HeroImageID
    on COALandingPage_Live (HeroImageID);

create table COALandingPage_TrainingPartners
(
    ID               int auto_increment
        primary key,
    COALandingPageID int default 0 not null,
    CompanyID        int default 0 not null,
    `Order`          int default 0 not null
)
    charset = latin1;

create index COALandingPageID
    on COALandingPage_TrainingPartners (COALandingPageID);

create index CompanyID
    on COALandingPage_TrainingPartners (CompanyID);

create table COALandingPage_versions
(
    ID                      int auto_increment
        primary key,
    RecordID                int              default 0   not null,
    Version                 int              default 0   not null,
    BannerTitle             mediumtext charset utf8      null,
    BannerText              mediumtext charset utf8      null,
    ExamDetails             mediumtext charset utf8      null,
    HandBookLink            mediumtext charset utf8      null,
    GetStartedURL           mediumtext charset utf8      null,
    GetStartedLabel         mediumtext charset utf8      null,
    GetStartedURL2          mediumtext charset utf8      null,
    GetStartedLabel2        mediumtext charset utf8      null,
    GetStartedURL3          mediumtext charset utf8      null,
    GetStartedLabel3        mediumtext charset utf8      null,
    HideFee                 tinyint unsigned default '0' not null,
    AlreadyRegisteredURL    mediumtext charset utf8      null,
    ExamCost                mediumtext charset utf8      null,
    ExamSpecialCost         mediumtext charset utf8      null,
    ExamCostSpecialOffer    mediumtext charset utf8      null,
    ExamFormat              mediumtext charset utf8      null,
    ExamIDRequirements      mediumtext charset utf8      null,
    ExamCertificationPeriod mediumtext charset utf8      null,
    ExamRetake              mediumtext charset utf8      null,
    ExamDuration            mediumtext charset utf8      null,
    ExamSystemRequirements  mediumtext charset utf8      null,
    ExamScoring             mediumtext charset utf8      null,
    ExamLanguage            mediumtext charset utf8      null,
    ExamHowLongSchedule     mediumtext charset utf8      null,
    GetStartedText          mediumtext charset utf8      null,
    HidePurchaseExam        tinyint unsigned default '0' not null,
    HideVirtualExam         tinyint unsigned default '0' not null,
    HideHowGetStarted       tinyint unsigned default '0' not null,
    HeroImageID             int                          null,
    constraint RecordID_Version
        unique (RecordID, Version)
)
    charset = latin1;

create index HeroImageID
    on COALandingPage_versions (HeroImageID);

create index RecordID
    on COALandingPage_versions (RecordID);

create index Version
    on COALandingPage_versions (Version);

create table COAProcessedFile
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('COAProcessedFile') charset utf8 default 'COAProcessedFile' null,
    LastEdited datetime                                                          null,
    Created    datetime                                                          null,
    Name       varchar(255) charset utf8                                         null,
    TimeStamp  int                                    default 0                  not null
)
    charset = latin1;

create index ClassName
    on COAProcessedFile (ClassName);

create table COAVerifyPage
(
    ID      int auto_increment
        primary key,
    TosText mediumtext charset utf8 null
)
    charset = latin1;

create table COAVerifyPage_Live
(
    ID      int auto_increment
        primary key,
    TosText mediumtext charset utf8 null
)
    charset = latin1;

create table COAVerifyPage_versions
(
    ID       int auto_increment
        primary key,
    RecordID int default 0           not null,
    Version  int default 0           not null,
    TosText  mediumtext charset utf8 null,
    constraint RecordID_Version
        unique (RecordID, Version)
)
    charset = latin1;

create index RecordID
    on COAVerifyPage_versions (RecordID);

create index Version
    on COAVerifyPage_versions (Version);

create table CalendarSyncErrorEmailRequest
(
    ID                 int auto_increment
        primary key,
    CalendarSyncInfoID int null
)
    charset = latin1;

create index CalendarSyncInfoID
    on CalendarSyncErrorEmailRequest (CalendarSyncInfoID);

create table CalendarSyncInfo
(
    ID                 int auto_increment
        primary key,
    ClassName          enum ('CalendarSyncInfo', 'CalendarSyncInfoCalDav', 'CalendarSyncInfoOAuth2') charset utf8 default 'CalendarSyncInfo' null,
    LastEdited         datetime                                                                                                              null,
    Created            datetime                                                                                                              null,
    Provider           enum ('Google', 'Outlook', 'iCloud') charset utf8                                          default 'Google'           null,
    CalendarExternalId varchar(512) charset utf8                                                                                             null,
    ETag               varchar(512) charset utf8                                                                                             null,
    Revoked            tinyint unsigned                                                                           default '0'                not null,
    SummitID           int                                                                                                                   null,
    OwnerID            int                                                                                                                   null
)
    charset = latin1;

create index ClassName
    on CalendarSyncInfo (ClassName);

create index OwnerID
    on CalendarSyncInfo (OwnerID);

create index SummitID
    on CalendarSyncInfo (SummitID);

create table CalendarSyncInfoCalDav
(
    ID                  int auto_increment
        primary key,
    UserName            varchar(254) charset utf8 null,
    UserPassword        mediumtext charset utf8   null,
    UserPrincipalURL    varchar(512) charset utf8 null,
    CalendarDisplayName varchar(512) charset utf8 null,
    CalendarSyncToken   varchar(512) charset utf8 null
)
    charset = latin1;

create table CalendarSyncInfoOAuth2
(
    ID           int auto_increment
        primary key,
    AccessToken  mediumtext charset utf8 null,
    RefreshToken mediumtext charset utf8 null
)
    charset = latin1;

create table CandidateNomination
(
    ID          int auto_increment
        primary key,
    ClassName   enum ('CandidateNomination') charset utf8 default 'CandidateNomination' null,
    LastEdited  datetime                                                                null,
    Created     datetime                                                                null,
    MemberID    int                                                                     null,
    CandidateID int                                                                     null,
    ElectionID  int                                                                     null
)
    charset = latin1;

create index CandidateID
    on CandidateNomination (CandidateID);

create index ClassName
    on CandidateNomination (ClassName);

create index ElectionID
    on CandidateNomination (ElectionID);

create index MemberID
    on CandidateNomination (MemberID);

create table CaseOfStudy
(
    ID     int auto_increment
        primary key,
    LogoID int null
)
    charset = latin1;

create index LogoID
    on CaseOfStudy (LogoID);

create table CertifiedOpenStackAdministratorExam
(
    ID                          int auto_increment
        primary key,
    ClassName                   enum ('CertifiedOpenStackAdministratorExam') charset utf8                                                 default 'CertifiedOpenStackAdministratorExam' null,
    LastEdited                  datetime                                                                                                                                                null,
    Created                     datetime                                                                                                                                                null,
    ExternalID                  varchar(255) charset utf8                                                                                                                               null,
    ExpirationDate              datetime                                                                                                                                                null,
    PassFailDate                datetime                                                                                                                                                null,
    ModifiedDate                datetime                                                                                                                                                null,
    Status                      enum ('None', 'New', 'Pending', 'Pass', 'No Pass', 'No Pending', 'Invalidated', 'Cancelled') charset utf8 default 'None'                                null,
    Code                        varchar(255) charset utf8                                                                                                                               null,
    CertificationNumber         varchar(255) charset utf8                                                                                                                               null,
    CertificationStatus         enum ('None', 'Achieved', 'InProgress', 'Expired', 'Renewed', 'In Appeals', 'Revoked') charset utf8       default 'None'                                null,
    CertificationExpirationDate datetime                                                                                                                                                null,
    TrackID                     varchar(512) charset utf8                                                                                                                               null,
    TrackModifiedDate           datetime                                                                                                                                                null,
    CandidateName               varchar(512) charset utf8                                                                                                                               null,
    CandidateNameFirstName      varchar(512) charset utf8                                                                                                                               null,
    CandidateNameLastName       varchar(512) charset utf8                                                                                                                               null,
    CandidateEmail              varchar(512) charset utf8                                                                                                                               null,
    CandidateExternalID         varchar(512) charset utf8                                                                                                                               null,
    CompletedDate               datetime                                                                                                                                                null,
    OwnerID                     int                                                                                                                                                     null
)
    charset = latin1;

create index ClassName
    on CertifiedOpenStackAdministratorExam (ClassName);

create index OwnerID
    on CertifiedOpenStackAdministratorExam (OwnerID);

create table ChatTeam
(
    ID          int auto_increment
        primary key,
    ClassName   enum ('ChatTeam') charset utf8 default 'ChatTeam' null,
    LastEdited  datetime                                          null,
    Created     datetime                                          null,
    Name        mediumtext charset utf8                           null,
    Description mediumtext charset utf8                           null,
    OwnerID     int                                               null
)
    charset = latin1;

create index ClassName
    on ChatTeam (ClassName);

create index OwnerID
    on ChatTeam (OwnerID);

create table ChatTeamInvitation
(
    ID           int auto_increment
        primary key,
    ClassName    enum ('ChatTeamInvitation') charset utf8     default 'ChatTeamInvitation' null,
    LastEdited   datetime                                                                  null,
    Created      datetime                                                                  null,
    Permission   enum ('READ', 'WRITE', 'ADMIN') charset utf8 default 'READ'               null,
    Accepted     tinyint unsigned                             default '0'                  not null,
    AcceptedDate datetime                                                                  null,
    InviterID    int                                                                       null,
    InviteeID    int                                                                       null,
    TeamID       int                                                                       null
)
    charset = latin1;

create index ClassName
    on ChatTeamInvitation (ClassName);

create index InviteeID
    on ChatTeamInvitation (InviteeID);

create index InviterID
    on ChatTeamInvitation (InviterID);

create index TeamID
    on ChatTeamInvitation (TeamID);

create table ChatTeamPushNotificationMessage
(
    ID         int auto_increment
        primary key,
    ChatTeamID int null
)
    charset = latin1;

create index ChatTeamID
    on ChatTeamPushNotificationMessage (ChatTeamID);

create table ChatTeam_Members
(
    ID         int auto_increment
        primary key,
    ChatTeamID int                                          default 0      not null,
    MemberID   int                                          default 0      not null,
    Permission enum ('READ', 'WRITE', 'ADMIN') charset utf8 default 'READ' null
)
    charset = latin1;

create index ChatTeamID
    on ChatTeam_Members (ChatTeamID);

create index MemberID
    on ChatTeam_Members (MemberID);

create table CloudImageCachedStore
(
    ID            int auto_increment
        primary key,
    ClassName     enum ('CloudImageCachedStore') charset utf8  default 'CloudImageCachedStore' null,
    LastEdited    datetime                                                                     null,
    Created       datetime                                                                     null,
    Filename      varchar(255) charset utf8                                                    null,
    CloudStatus   enum ('Local', 'Live', 'Error') charset utf8 default 'Local'                 null,
    CloudSize     int                                          default 0                       not null,
    CloudMetaJson mediumtext charset utf8                                                      null,
    SourceID      int                                                                          null
)
    charset = latin1;

create index ClassName
    on CloudImageCachedStore (ClassName);

create index Filename
    on CloudImageCachedStore (Filename);

create index SourceID
    on CloudImageCachedStore (SourceID);

create table CloudService
(
    ID int auto_increment
        primary key
)
    charset = latin1;

create table CloudServiceOffered
(
    ID   int auto_increment
        primary key,
    Type varchar(50) charset utf8 null
)
    charset = latin1;

create table CloudServiceOfferedDraft_PricingSchemas
(
    ID                         int auto_increment
        primary key,
    CloudServiceOfferedDraftID int default 0 not null,
    PricingSchemaTypeID        int default 0 not null
)
    charset = latin1;

create index CloudServiceOfferedDraftID
    on CloudServiceOfferedDraft_PricingSchemas (CloudServiceOfferedDraftID);

create index PricingSchemaTypeID
    on CloudServiceOfferedDraft_PricingSchemas (PricingSchemaTypeID);

create table CloudServiceOffered_PricingSchemas
(
    ID                    int auto_increment
        primary key,
    CloudServiceOfferedID int default 0 not null,
    PricingSchemaTypeID   int default 0 not null
)
    charset = latin1;

create index CloudServiceOfferedID
    on CloudServiceOffered_PricingSchemas (CloudServiceOfferedID);

create index PricingSchemaTypeID
    on CloudServiceOffered_PricingSchemas (PricingSchemaTypeID);

create table CommMember
(
    ID          int auto_increment
        primary key,
    ClassName   enum ('CommMember') charset utf8 default 'CommMember' null,
    LastEdited  datetime                                              null,
    Created     datetime                                              null,
    Name        varchar(255) charset utf8                             null,
    Description mediumtext charset utf8                               null,
    CommPageID  int                                                   null,
    PhotoID     int                                                   null
)
    charset = latin1;

create index ClassName
    on CommMember (ClassName);

create index CommPageID
    on CommMember (CommPageID);

create index PhotoID
    on CommMember (PhotoID);

create table CommunityAward
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('CommunityAward') charset utf8 default 'CommunityAward' null,
    LastEdited datetime                                                      null,
    Created    datetime                                                      null,
    Name       mediumtext charset utf8                                       null,
    SummitID   int                                                           null
)
    charset = latin1;

create index ClassName
    on CommunityAward (ClassName);

create index SummitID
    on CommunityAward (SummitID);

create table CommunityContributor
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('CommunityContributor') charset utf8 default 'CommunityContributor' null,
    LastEdited datetime                                                                  null,
    Created    datetime                                                                  null,
    FirstName  varchar(50) charset utf8                                                  null,
    LastName   varchar(50) charset utf8                                                  null,
    Email      varchar(50) charset utf8                                                  null,
    Awards     mediumtext charset utf8                                                   null,
    MemberID   int                                                                       null
)
    charset = latin1;

create index ClassName
    on CommunityContributor (ClassName);

create index MemberID
    on CommunityContributor (MemberID);

create table CommunityContributor_Awards
(
    ID                     int auto_increment
        primary key,
    CommunityContributorID int default 0 not null,
    CommunityAwardID       int default 0 not null
)
    charset = latin1;

create index CommunityAwardID
    on CommunityContributor_Awards (CommunityAwardID);

create index CommunityContributorID
    on CommunityContributor_Awards (CommunityContributorID);

create table CommunityPage
(
    ID         int auto_increment
        primary key,
    TopSection mediumtext charset utf8 null
)
    charset = latin1;

create table CommunityPageBis
(
    ID        int auto_increment
        primary key,
    TopBanner mediumtext charset utf8 null
)
    charset = latin1;

create table CommunityPageBis_Ambassadors
(
    ID                 int auto_increment
        primary key,
    CommunityPageBisID int default 0 not null,
    MemberID           int default 0 not null,
    `Order`            int default 0 not null
)
    charset = latin1;

create index CommunityPageBisID
    on CommunityPageBis_Ambassadors (CommunityPageBisID);

create index MemberID
    on CommunityPageBis_Ambassadors (MemberID);

create table CommunityPageBis_CommunityManagers
(
    ID                 int auto_increment
        primary key,
    CommunityPageBisID int default 0 not null,
    MemberID           int default 0 not null,
    `Order`            int default 0 not null
)
    charset = latin1;

create index CommunityPageBisID
    on CommunityPageBis_CommunityManagers (CommunityPageBisID);

create index MemberID
    on CommunityPageBis_CommunityManagers (MemberID);

create table CommunityPageBis_Live
(
    ID        int auto_increment
        primary key,
    TopBanner mediumtext charset utf8 null
)
    charset = latin1;

create table CommunityPageBis_versions
(
    ID        int auto_increment
        primary key,
    RecordID  int default 0           not null,
    Version   int default 0           not null,
    TopBanner mediumtext charset utf8 null,
    constraint RecordID_Version
        unique (RecordID, Version)
)
    charset = latin1;

create index RecordID
    on CommunityPageBis_versions (RecordID);

create index Version
    on CommunityPageBis_versions (Version);

create table CommunityPage_Live
(
    ID         int auto_increment
        primary key,
    TopSection mediumtext charset utf8 null
)
    charset = latin1;

create table CommunityPage_versions
(
    ID         int auto_increment
        primary key,
    RecordID   int default 0           not null,
    Version    int default 0           not null,
    TopSection mediumtext charset utf8 null,
    constraint RecordID_Version
        unique (RecordID, Version)
)
    charset = latin1;

create index RecordID
    on CommunityPage_versions (RecordID);

create index Version
    on CommunityPage_versions (Version);

create table Company
(
    ID                int auto_increment
        primary key,
    ClassName         enum ('Company') charset utf8                                                     default 'Company' null,
    LastEdited        datetime                                                                                            null,
    Created           datetime                                                                                            null,
    Name              mediumtext charset utf8                                                                             null,
    URL               mediumtext charset utf8                                                                             null,
    DisplayOnSite     tinyint unsigned                                                                  default '0'       not null,
    Featured          tinyint unsigned                                                                  default '0'       not null,
    City              varchar(255) charset utf8                                                                           null,
    State             varchar(255) charset utf8                                                                           null,
    Country           varchar(255) charset utf8                                                                           null,
    Description       mediumtext charset utf8                                                                             null,
    Industry          mediumtext charset utf8                                                                             null,
    Products          mediumtext charset utf8                                                                             null,
    Contributions     mediumtext charset utf8                                                                             null,
    ContactEmail      mediumtext charset utf8                                                                             null,
    MemberLevel       enum ('Platinum', 'Gold', 'StartUp', 'Corporate', 'Mention', 'None') charset utf8 default 'None'    null,
    AdminEmail        mediumtext charset utf8                                                                             null,
    URLSegment        mediumtext charset utf8                                                                             null,
    Color             mediumtext charset utf8                                                                             null,
    Overview          mediumtext charset utf8                                                                             null,
    Commitment        mediumtext charset utf8                                                                             null,
    CommitmentAuthor  varchar(255) charset utf8                                                                           null,
    isDeleted         tinyint unsigned                                                                  default '0'       not null,
    CCLASigned        tinyint unsigned                                                                  default '0'       not null,
    CCLADate          datetime                                                                                            null,
    CompanyListPageID int                                                                                                 null,
    LogoID            int                                                                                                 null,
    BigLogoID         int                                                                                                 null,
    SubmitterID       int                                                                                                 null,
    CompanyAdminID    int                                                                                                 null
)
    charset = latin1;

create index BigLogoID
    on Company (BigLogoID);

create index ClassName
    on Company (ClassName);

create index CompanyAdminID
    on Company (CompanyAdminID);

create index CompanyListPageID
    on Company (CompanyListPageID);

create index LogoID
    on Company (LogoID);

create index SubmitterID
    on Company (SubmitterID);

create table CompanyListPage_Donors
(
    ID                int auto_increment
        primary key,
    CompanyListPageID int default 0 not null,
    CompanyID         int default 0 not null,
    SortOrder         int default 0 not null
)
    charset = latin1;

create index CompanyID
    on CompanyListPage_Donors (CompanyID);

create index CompanyListPageID
    on CompanyListPage_Donors (CompanyListPageID);

create table CompanyService
(
    ID                int auto_increment
        primary key,
    ClassName         enum ('CompanyService', 'RegionalSupportedCompanyService', 'OpenStackImplementation', 'Appliance', 'Distribution', 'CloudService', 'PrivateCloudService', 'PublicCloudService', 'RemoteCloudService', 'Consultant', 'TrainingService') charset utf8 default 'CompanyService' null,
    LastEdited        datetime                                                                                                                                                                                                                                                                     null,
    Created           datetime                                                                                                                                                                                                                                                                     null,
    Name              varchar(255) charset utf8                                                                                                                                                                                                                                                    null,
    Slug              varchar(255) charset utf8                                                                                                                                                                                                                                                    null,
    Overview          mediumtext charset utf8                                                                                                                                                                                                                                                      null,
    Call2ActionUri    mediumtext charset utf8                                                                                                                                                                                                                                                      null,
    Active            tinyint unsigned                                                                                                                                                                                                                                    default '0'              not null,
    CompanyID         int                                                                                                                                                                                                                                                                          null,
    MarketPlaceTypeID int                                                                                                                                                                                                                                                                          null,
    EditedByID        int                                                                                                                                                                                                                                                                          null,
    constraint Company_Name_Class
        unique (Name, CompanyID, ClassName)
)
    charset = latin1;

create index ClassName
    on CompanyService (ClassName);

create index CompanyID
    on CompanyService (CompanyID);

create index EditedByID
    on CompanyService (EditedByID);

create index MarketPlaceTypeID
    on CompanyService (MarketPlaceTypeID);

create table CompanyServiceDraft
(
    ID                int auto_increment
        primary key,
    ClassName         enum ('CompanyServiceDraft', 'RegionalSupportedCompanyServiceDraft', 'OpenStackImplementationDraft', 'ApplianceDraft', 'DistributionDraft', 'CloudServiceDraft', 'PrivateCloudServiceDraft', 'PublicCloudServiceDraft', 'RemoteCloudServiceDraft', 'ConsultantDraft') charset utf8 default 'CompanyServiceDraft' null,
    LastEdited        datetime                                                                                                                                                                                                                                                                                                         null,
    Created           datetime                                                                                                                                                                                                                                                                                                         null,
    Name              varchar(255) charset utf8                                                                                                                                                                                                                                                                                        null,
    Slug              varchar(255) charset utf8                                                                                                                                                                                                                                                                                        null,
    Overview          mediumtext charset utf8                                                                                                                                                                                                                                                                                          null,
    Call2ActionUri    mediumtext charset utf8                                                                                                                                                                                                                                                                                          null,
    Active            tinyint unsigned                                                                                                                                                                                                                                                                   default '0'                   not null,
    Published         tinyint unsigned                                                                                                                                                                                                                                                                   default '0'                   not null,
    LiveServiceID     int                                                                                                                                                                                                                                                                                                              null,
    CompanyID         int                                                                                                                                                                                                                                                                                                              null,
    MarketPlaceTypeID int                                                                                                                                                                                                                                                                                                              null,
    EditedByID        int                                                                                                                                                                                                                                                                                                              null,
    constraint Company_Name_Class
        unique (Name, CompanyID, ClassName)
)
    charset = latin1;

create index ClassName
    on CompanyServiceDraft (ClassName);

create index CompanyID
    on CompanyServiceDraft (CompanyID);

create index EditedByID
    on CompanyServiceDraft (EditedByID);

create index LiveServiceID
    on CompanyServiceDraft (LiveServiceID);

create index MarketPlaceTypeID
    on CompanyServiceDraft (MarketPlaceTypeID);

create table CompanyServiceResource
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('CompanyServiceResource') charset utf8 default 'CompanyServiceResource' null,
    LastEdited datetime                                                                      null,
    Created    datetime                                                                      null,
    Name       varchar(50) charset utf8                                                      null,
    Uri        mediumtext charset utf8                                                       null,
    `Order`    int                                          default 0                        not null,
    OwnerID    int                                                                           null,
    constraint Owner_Name
        unique (Name, OwnerID)
)
    charset = latin1;

create index ClassName
    on CompanyServiceResource (ClassName);

create index OwnerID
    on CompanyServiceResource (OwnerID);

create table CompanyServiceResourceDraft
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('CompanyServiceResourceDraft') charset utf8 default 'CompanyServiceResourceDraft' null,
    LastEdited datetime                                                                                null,
    Created    datetime                                                                                null,
    Name       varchar(50) charset utf8                                                                null,
    Uri        mediumtext charset utf8                                                                 null,
    `Order`    int                                               default 0                             not null,
    OwnerID    int                                                                                     null,
    constraint Owner_Name
        unique (Name, OwnerID)
)
    charset = latin1;

create index ClassName
    on CompanyServiceResourceDraft (ClassName);

create index OwnerID
    on CompanyServiceResourceDraft (OwnerID);

create table CompanyServiceUpdateRecord
(
    ID               int auto_increment
        primary key,
    ClassName        enum ('CompanyServiceUpdateRecord') charset utf8 default 'CompanyServiceUpdateRecord' null,
    LastEdited       datetime                                                                              null,
    Created          datetime                                                                              null,
    CompanyServiceID int                                                                                   null,
    EditorID         int                                                                                   null
)
    charset = latin1;

create index ClassName
    on CompanyServiceUpdateRecord (ClassName);

create index CompanyServiceID
    on CompanyServiceUpdateRecord (CompanyServiceID);

create index EditorID
    on CompanyServiceUpdateRecord (EditorID);

create table Company_Administrators
(
    ID        int auto_increment
        primary key,
    CompanyID int default 0 not null,
    MemberID  int default 0 not null,
    GroupID   int default 0 not null
)
    charset = latin1;

create index CompanyID
    on Company_Administrators (CompanyID);

create index MemberID
    on Company_Administrators (MemberID);

create table ConferenceLivePage
(
    ID                   int auto_increment
        primary key,
    GAConversionId       mediumtext charset utf8      null,
    GAConversionLanguage mediumtext charset utf8      null,
    GAConversionFormat   mediumtext charset utf8      null,
    GAConversionColor    mediumtext charset utf8      null,
    GAConversionLabel    mediumtext charset utf8      null,
    GAConversionValue    int              default 0   not null,
    GARemarketingOnly    tinyint unsigned default '0' not null,
    SummitID             int                          null
)
    charset = latin1;

create index SummitID
    on ConferenceLivePage (SummitID);

create table ConferenceLivePage_Live
(
    ID                   int auto_increment
        primary key,
    GAConversionId       mediumtext charset utf8      null,
    GAConversionLanguage mediumtext charset utf8      null,
    GAConversionFormat   mediumtext charset utf8      null,
    GAConversionColor    mediumtext charset utf8      null,
    GAConversionLabel    mediumtext charset utf8      null,
    GAConversionValue    int              default 0   not null,
    GARemarketingOnly    tinyint unsigned default '0' not null,
    SummitID             int                          null
)
    charset = latin1;

create index SummitID
    on ConferenceLivePage_Live (SummitID);

create table ConferenceLivePage_versions
(
    ID                   int auto_increment
        primary key,
    RecordID             int              default 0   not null,
    Version              int              default 0   not null,
    GAConversionId       mediumtext charset utf8      null,
    GAConversionLanguage mediumtext charset utf8      null,
    GAConversionFormat   mediumtext charset utf8      null,
    GAConversionColor    mediumtext charset utf8      null,
    GAConversionLabel    mediumtext charset utf8      null,
    GAConversionValue    int              default 0   not null,
    GARemarketingOnly    tinyint unsigned default '0' not null,
    SummitID             int                          null,
    constraint RecordID_Version
        unique (RecordID, Version)
)
    charset = latin1;

create index RecordID
    on ConferenceLivePage_versions (RecordID);

create index SummitID
    on ConferenceLivePage_versions (SummitID);

create index Version
    on ConferenceLivePage_versions (Version);

create table ConferencePage
(
    ID                   int auto_increment
        primary key,
    HeaderArea           mediumtext charset utf8      null,
    Sidebar              mediumtext charset utf8      null,
    HeadlineSponsors     mediumtext charset utf8      null,
    GAConversionId       mediumtext charset utf8      null,
    GAConversionLanguage mediumtext charset utf8      null,
    GAConversionFormat   mediumtext charset utf8      null,
    GAConversionColor    mediumtext charset utf8      null,
    GAConversionLabel    mediumtext charset utf8      null,
    GAConversionValue    int              default 0   not null,
    GARemarketingOnly    tinyint unsigned default '0' not null,
    FBPixelId            mediumtext charset utf8      null,
    FBValue              mediumtext charset utf8      null,
    FBCurrency           mediumtext charset utf8      null,
    SummitID             int                          null,
    SummitImageID        int                          null
)
    charset = latin1;

create index SummitID
    on ConferencePage (SummitID);

create index SummitImageID
    on ConferencePage (SummitImageID);

create table ConferencePage_Live
(
    ID                   int auto_increment
        primary key,
    HeaderArea           mediumtext charset utf8      null,
    Sidebar              mediumtext charset utf8      null,
    HeadlineSponsors     mediumtext charset utf8      null,
    GAConversionId       mediumtext charset utf8      null,
    GAConversionLanguage mediumtext charset utf8      null,
    GAConversionFormat   mediumtext charset utf8      null,
    GAConversionColor    mediumtext charset utf8      null,
    GAConversionLabel    mediumtext charset utf8      null,
    GAConversionValue    int              default 0   not null,
    GARemarketingOnly    tinyint unsigned default '0' not null,
    FBPixelId            mediumtext charset utf8      null,
    FBValue              mediumtext charset utf8      null,
    FBCurrency           mediumtext charset utf8      null,
    SummitID             int                          null,
    SummitImageID        int                          null
)
    charset = latin1;

create index SummitID
    on ConferencePage_Live (SummitID);

create index SummitImageID
    on ConferencePage_Live (SummitImageID);

create table ConferencePage_versions
(
    ID                   int auto_increment
        primary key,
    RecordID             int              default 0   not null,
    Version              int              default 0   not null,
    HeaderArea           mediumtext charset utf8      null,
    Sidebar              mediumtext charset utf8      null,
    HeadlineSponsors     mediumtext charset utf8      null,
    GAConversionId       mediumtext charset utf8      null,
    GAConversionLanguage mediumtext charset utf8      null,
    GAConversionFormat   mediumtext charset utf8      null,
    GAConversionColor    mediumtext charset utf8      null,
    GAConversionLabel    mediumtext charset utf8      null,
    GAConversionValue    int              default 0   not null,
    GARemarketingOnly    tinyint unsigned default '0' not null,
    FBPixelId            mediumtext charset utf8      null,
    FBValue              mediumtext charset utf8      null,
    FBCurrency           mediumtext charset utf8      null,
    SummitID             int                          null,
    SummitImageID        int                          null,
    constraint RecordID_Version
        unique (RecordID, Version)
)
    charset = latin1;

create index RecordID
    on ConferencePage_versions (RecordID);

create index SummitID
    on ConferencePage_versions (SummitID);

create index SummitImageID
    on ConferencePage_versions (SummitImageID);

create index Version
    on ConferencePage_versions (Version);

create table ConferenceSubPage
(
    ID          int auto_increment
        primary key,
    HideSideBar tinyint unsigned default '0' not null
)
    charset = latin1;

create table ConferenceSubPage_Live
(
    ID          int auto_increment
        primary key,
    HideSideBar tinyint unsigned default '0' not null
)
    charset = latin1;

create table ConferenceSubPage_versions
(
    ID          int auto_increment
        primary key,
    RecordID    int              default 0   not null,
    Version     int              default 0   not null,
    HideSideBar tinyint unsigned default '0' not null,
    constraint RecordID_Version
        unique (RecordID, Version)
)
    charset = latin1;

create index RecordID
    on ConferenceSubPage_versions (RecordID);

create index Version
    on ConferenceSubPage_versions (Version);

create table ConfigurationManagementType
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('ConfigurationManagementType') charset utf8 default 'ConfigurationManagementType' null,
    LastEdited datetime                                                                                null,
    Created    datetime                                                                                null,
    Type       varchar(50) charset utf8                                                                null,
    constraint Type
        unique (Type)
)
    charset = latin1;

create index ClassName
    on ConfigurationManagementType (ClassName);

create table Consultant
(
    ID int auto_increment
        primary key
)
    charset = latin1;

create table ConsultantClient
(
    ID           int auto_increment
        primary key,
    ClassName    enum ('ConsultantClient') charset utf8 default 'ConsultantClient' null,
    LastEdited   datetime                                                          null,
    Created      datetime                                                          null,
    Name         varchar(50) charset utf8                                          null,
    `Order`      int                                    default 0                  not null,
    ConsultantID int                                                               null,
    constraint Name_Owner
        unique (Name, ConsultantID)
)
    charset = latin1;

create index ClassName
    on ConsultantClient (ClassName);

create index ConsultantID
    on ConsultantClient (ConsultantID);

create table ConsultantClientDraft
(
    ID           int auto_increment
        primary key,
    ClassName    enum ('ConsultantClientDraft') charset utf8 default 'ConsultantClientDraft' null,
    LastEdited   datetime                                                                    null,
    Created      datetime                                                                    null,
    Name         varchar(50) charset utf8                                                    null,
    `Order`      int                                         default 0                       not null,
    ConsultantID int                                                                         null,
    constraint Name_Owner
        unique (Name, ConsultantID)
)
    charset = latin1;

create index ClassName
    on ConsultantClientDraft (ClassName);

create index ConsultantID
    on ConsultantClientDraft (ConsultantID);

create table ConsultantDraft_ConfigurationManagementExpertises
(
    ID                            int auto_increment
        primary key,
    ConsultantDraftID             int default 0 not null,
    ConfigurationManagementTypeID int default 0 not null
)
    charset = latin1;

create index ConfigurationManagementTypeID
    on ConsultantDraft_ConfigurationManagementExpertises (ConfigurationManagementTypeID);

create index ConsultantDraftID
    on ConsultantDraft_ConfigurationManagementExpertises (ConsultantDraftID);

create table ConsultantDraft_ExpertiseAreas
(
    ID                   int auto_increment
        primary key,
    ConsultantDraftID    int default 0 not null,
    OpenStackComponentID int default 0 not null
)
    charset = latin1;

create index ConsultantDraftID
    on ConsultantDraft_ExpertiseAreas (ConsultantDraftID);

create index OpenStackComponentID
    on ConsultantDraft_ExpertiseAreas (OpenStackComponentID);

create table ConsultantDraft_ServicesOffered
(
    ID                             int auto_increment
        primary key,
    ConsultantDraftID              int default 0 not null,
    ConsultantServiceOfferedTypeID int default 0 not null,
    RegionID                       int default 0 not null
)
    charset = latin1;

create index ConsultantDraftID
    on ConsultantDraft_ServicesOffered (ConsultantDraftID);

create index ConsultantServiceOfferedTypeID
    on ConsultantDraft_ServicesOffered (ConsultantServiceOfferedTypeID);

create table ConsultantDraft_SpokenLanguages
(
    ID                int auto_increment
        primary key,
    ConsultantDraftID int default 0 not null,
    SpokenLanguageID  int default 0 not null,
    `Order`           int default 0 not null
)
    charset = latin1;

create index ConsultantDraftID
    on ConsultantDraft_SpokenLanguages (ConsultantDraftID);

create index SpokenLanguageID
    on ConsultantDraft_SpokenLanguages (SpokenLanguageID);

create table ConsultantServiceOfferedType
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('ConsultantServiceOfferedType') charset utf8 default 'ConsultantServiceOfferedType' null,
    LastEdited datetime                                                                                  null,
    Created    datetime                                                                                  null,
    Type       mediumtext charset utf8                                                                   null
)
    charset = latin1;

create index ClassName
    on ConsultantServiceOfferedType (ClassName);

create table Consultant_ConfigurationManagementExpertises
(
    ID                            int auto_increment
        primary key,
    ConsultantID                  int default 0 not null,
    ConfigurationManagementTypeID int default 0 not null
)
    charset = latin1;

create index ConfigurationManagementTypeID
    on Consultant_ConfigurationManagementExpertises (ConfigurationManagementTypeID);

create index ConsultantID
    on Consultant_ConfigurationManagementExpertises (ConsultantID);

create table Consultant_ExpertiseAreas
(
    ID                   int auto_increment
        primary key,
    ConsultantID         int default 0 not null,
    OpenStackComponentID int default 0 not null
)
    charset = latin1;

create index ConsultantID
    on Consultant_ExpertiseAreas (ConsultantID);

create index OpenStackComponentID
    on Consultant_ExpertiseAreas (OpenStackComponentID);

create table Consultant_ServicesOffered
(
    ID                             int auto_increment
        primary key,
    ConsultantID                   int default 0 not null,
    ConsultantServiceOfferedTypeID int default 0 not null,
    RegionID                       int default 0 not null
)
    charset = latin1;

create index ConsultantID
    on Consultant_ServicesOffered (ConsultantID);

create index ConsultantServiceOfferedTypeID
    on Consultant_ServicesOffered (ConsultantServiceOfferedTypeID);

create table Consultant_SpokenLanguages
(
    ID               int auto_increment
        primary key,
    ConsultantID     int default 0 not null,
    SpokenLanguageID int default 0 not null,
    `Order`          int default 0 not null
)
    charset = latin1;

create index ConsultantID
    on Consultant_SpokenLanguages (ConsultantID);

create index SpokenLanguageID
    on Consultant_SpokenLanguages (SpokenLanguageID);

create table Continent
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('Continent') charset utf8 default 'Continent' null,
    LastEdited datetime                                            null,
    Created    datetime                                            null,
    Name       varchar(255) charset utf8                           null
)
    charset = latin1;

create index ClassName
    on Continent (ClassName);

create table Contract
(
    ID                 int auto_increment
        primary key,
    ClassName          enum ('Contract') charset utf8 default 'Contract' null,
    LastEdited         datetime                                          null,
    Created            datetime                                          null,
    ContractSigned     tinyint unsigned               default '0'        not null,
    ContractStart      date                                              null,
    ContractEnd        date                                              null,
    EchosignID         mediumtext charset utf8                           null,
    Status             mediumtext charset utf8                           null,
    CompanyID          int                                               null,
    ContractTemplateID int                                               null
)
    charset = latin1;

create index ClassName
    on Contract (ClassName);

create index CompanyID
    on Contract (CompanyID);

create index ContractTemplateID
    on Contract (ContractTemplateID);

create table ContractTemplate
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('ContractTemplate', 'MarketplaceContractTemplate') charset utf8 default 'ContractTemplate' null,
    LastEdited datetime                                                                                         null,
    Created    datetime                                                                                         null,
    Name       varchar(50) charset utf8                                                                         null,
    Duration   int                                                                   default 0                  not null,
    AutoRenew  tinyint unsigned                                                      default '0'                not null,
    PDFID      int                                                                                              null
)
    charset = latin1;

create index ClassName
    on ContractTemplate (ClassName);

create index PDFID
    on ContractTemplate (PDFID);

create table ContributorsIngestRequest
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('ContributorsIngestRequest') charset utf8 default 'ContributorsIngestRequest' null,
    LastEdited datetime                                                                            null,
    Created    datetime                                                                            null,
    IsRunning  tinyint unsigned                                default '0'                         not null
)
    charset = latin1;

create index ClassName
    on ContributorsIngestRequest (ClassName);

create table CustomerCaseStudy
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('CustomerCaseStudy') charset utf8 default 'CustomerCaseStudy' null,
    LastEdited datetime                                                            null,
    Created    datetime                                                            null,
    Name       varchar(50) charset utf8                                            null,
    Uri        mediumtext charset utf8                                             null,
    `Order`    int                                     default 0                   not null,
    OwnerID    int                                                                 null,
    constraint Owner_Name
        unique (Name, OwnerID)
)
    charset = latin1;

create index ClassName
    on CustomerCaseStudy (ClassName);

create index OwnerID
    on CustomerCaseStudy (OwnerID);

create table CustomerCaseStudyDraft
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('CustomerCaseStudyDraft') charset utf8 default 'CustomerCaseStudyDraft' null,
    LastEdited datetime                                                                      null,
    Created    datetime                                                                      null,
    Name       varchar(50) charset utf8                                                      null,
    Uri        mediumtext charset utf8                                                       null,
    `Order`    int                                          default 0                        not null,
    OwnerID    int                                                                           null,
    constraint Owner_Name
        unique (Name, OwnerID)
)
    charset = latin1;

create index ClassName
    on CustomerCaseStudyDraft (ClassName);

create index OwnerID
    on CustomerCaseStudyDraft (OwnerID);

create table DataCenterLocation
(
    ID                 int auto_increment
        primary key,
    ClassName          enum ('DataCenterLocation') charset utf8 default 'DataCenterLocation' null,
    LastEdited         datetime                                                              null,
    Created            datetime                                                              null,
    City               varchar(125) charset utf8                                             null,
    State              varchar(50) charset utf8                                              null,
    Country            varchar(5) charset utf8                                               null,
    Lat                decimal(9, 2)                            default 0.00                 not null,
    Lng                decimal(9, 2)                            default 0.00                 not null,
    CloudServiceID     int                                                                   null,
    DataCenterRegionID int                                                                   null,
    constraint City_State_Country_Service_Region
        unique (CloudServiceID, DataCenterRegionID, City, Country, State)
)
    charset = latin1;

create index ClassName
    on DataCenterLocation (ClassName);

create index CloudServiceID
    on DataCenterLocation (CloudServiceID);

create index DataCenterRegionID
    on DataCenterLocation (DataCenterRegionID);

create table DataCenterLocationDraft
(
    ID                 int auto_increment
        primary key,
    ClassName          enum ('DataCenterLocationDraft') charset utf8 default 'DataCenterLocationDraft' null,
    LastEdited         datetime                                                                        null,
    Created            datetime                                                                        null,
    City               varchar(125) charset utf8                                                       null,
    State              varchar(50) charset utf8                                                        null,
    Country            varchar(5) charset utf8                                                         null,
    Lat                decimal(9, 2)                                 default 0.00                      not null,
    Lng                decimal(9, 2)                                 default 0.00                      not null,
    CloudServiceID     int                                                                             null,
    DataCenterRegionID int                                                                             null,
    constraint City_State_Country_Service_Region
        unique (CloudServiceID, DataCenterRegionID, City, Country, State)
)
    charset = latin1;

create index ClassName
    on DataCenterLocationDraft (ClassName);

create index CloudServiceID
    on DataCenterLocationDraft (CloudServiceID);

create index DataCenterRegionID
    on DataCenterLocationDraft (DataCenterRegionID);

create table DataCenterRegion
(
    ID             int auto_increment
        primary key,
    ClassName      enum ('DataCenterRegion') charset utf8 default 'DataCenterRegion' null,
    LastEdited     datetime                                                          null,
    Created        datetime                                                          null,
    Name           varchar(100) charset utf8                                         null,
    Endpoint       varchar(512) charset utf8                                         null,
    Color          varchar(6) charset utf8                                           null,
    CloudServiceID int                                                               null
)
    charset = latin1;

create index ClassName
    on DataCenterRegion (ClassName);

create index CloudServiceID
    on DataCenterRegion (CloudServiceID);

create table DataCenterRegionDraft
(
    ID             int auto_increment
        primary key,
    ClassName      enum ('DataCenterRegionDraft') charset utf8 default 'DataCenterRegionDraft' null,
    LastEdited     datetime                                                                    null,
    Created        datetime                                                                    null,
    Name           varchar(100) charset utf8                                                   null,
    Endpoint       varchar(512) charset utf8                                                   null,
    Color          varchar(6) charset utf8                                                     null,
    CloudServiceID int                                                                         null
)
    charset = latin1;

create index ClassName
    on DataCenterRegionDraft (ClassName);

create index CloudServiceID
    on DataCenterRegionDraft (CloudServiceID);

create table DefaultPresentationType
(
    ID                     int auto_increment
        primary key,
    MaxSpeakers            int              default 0   not null,
    MinSpeakers            int              default 0   not null,
    MaxModerators          int              default 0   not null,
    MinModerators          int              default 0   not null,
    UseSpeakers            tinyint unsigned default '0' not null,
    AreSpeakersMandatory   tinyint unsigned default '0' not null,
    UseModerator           tinyint unsigned default '0' not null,
    IsModeratorMandatory   tinyint unsigned default '0' not null,
    ModeratorLabel         varchar(255) charset utf8    null,
    ShouldBeAvailableOnCFP tinyint unsigned default '0' not null
)
    charset = latin1;

create table DefaultSummitEventType
(
    ID                   int auto_increment
        primary key,
    ClassName            enum ('DefaultSummitEventType', 'DefaultPresentationType') charset utf8 default 'DefaultSummitEventType' null,
    LastEdited           datetime                                                                                                 null,
    Created              datetime                                                                                                 null,
    Type                 mediumtext charset utf8                                                                                  null,
    Color                mediumtext charset utf8                                                                                  null,
    BlackoutTimes        tinyint unsigned                                                        default '0'                      not null,
    UseSponsors          tinyint unsigned                                                        default '0'                      not null,
    AreSponsorsMandatory tinyint unsigned                                                        default '0'                      not null,
    AllowsAttachment     tinyint unsigned                                                        default '0'                      not null,
    IsPrivate            tinyint unsigned                                                        default '0'                      not null
)
    charset = latin1;

create index ClassName
    on DefaultSummitEventType (ClassName);

create table DefaultTrackTagGroup
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('DefaultTrackTagGroup') charset utf8 default 'DefaultTrackTagGroup' null,
    LastEdited datetime                                                                  null,
    Created    datetime                                                                  null,
    Name       varchar(50) charset utf8                                                  null,
    Label      varchar(50) charset utf8                                                  null,
    `Order`    int                                        default 0                      not null,
    Mandatory  tinyint unsigned                           default '0'                    not null
)
    charset = latin1;

create index ClassName
    on DefaultTrackTagGroup (ClassName);

create table DefaultTrackTagGroup_AllowedTags
(
    ID                     int auto_increment
        primary key,
    DefaultTrackTagGroupID int default 0 not null,
    TagID                  int default 0 not null
)
    charset = latin1;

create index DefaultTrackTagGroupID
    on DefaultTrackTagGroup_AllowedTags (DefaultTrackTagGroupID);

create index TagID
    on DefaultTrackTagGroup_AllowedTags (TagID);

create table DeletedDupeMember
(
    ID                     int auto_increment
        primary key,
    ClassName              enum ('DeletedDupeMember') charset utf8                                    default 'DeletedDupeMember' null,
    LastEdited             datetime                                                                                               null,
    Created                datetime                                                                                               null,
    MemberID               int                                                                        default 0                   not null,
    FirstName              varchar(50) charset utf8                                                                               null,
    Surname                varchar(50) charset utf8                                                                               null,
    Email                  varchar(256) charset utf8                                                                              null,
    Password               varchar(160) charset utf8                                                                              null,
    PasswordEncryption     varchar(50) charset utf8                                                                               null,
    Salt                   varchar(50) charset utf8                                                                               null,
    PasswordExpiry         date                                                                                                   null,
    LockedOutUntil         datetime                                                                                               null,
    Locale                 varchar(6) charset utf8                                                                                null,
    DateFormat             varchar(30) charset utf8                                                                               null,
    TimeFormat             varchar(30) charset utf8                                                                               null,
    SecondEmail            mediumtext charset utf8                                                                                null,
    ThirdEmail             mediumtext charset utf8                                                                                null,
    HasBeenEmailed         tinyint unsigned                                                           default '0'                 not null,
    ShirtSize              enum ('Extra Small', 'Small', 'Medium', 'Large', 'XL', 'XXL') charset utf8 default 'Extra Small'       null,
    StatementOfInterest    mediumtext charset utf8                                                                                null,
    Bio                    mediumtext charset utf8                                                                                null,
    FoodPreference         mediumtext charset utf8                                                                                null,
    OtherFood              mediumtext charset utf8                                                                                null,
    IRCHandle              mediumtext charset utf8                                                                                null,
    TwitterName            mediumtext charset utf8                                                                                null,
    Projects               mediumtext charset utf8                                                                                null,
    OtherProject           mediumtext charset utf8                                                                                null,
    SubscribedToNewsletter tinyint unsigned                                                           default '0'                 not null,
    JobTitle               mediumtext charset utf8                                                                                null,
    DisplayOnSite          tinyint unsigned                                                           default '0'                 not null,
    Role                   mediumtext charset utf8                                                                                null,
    LinkedInProfile        mediumtext charset utf8                                                                                null,
    Address                varchar(255) charset utf8                                                                              null,
    Suburb                 varchar(64) charset utf8                                                                               null,
    State                  varchar(64) charset utf8                                                                               null,
    Postcode               varchar(64) charset utf8                                                                               null,
    Country                varchar(2) charset utf8                                                                                null,
    City                   varchar(64) charset utf8                                                                               null,
    Gender                 varchar(32) charset utf8                                                                               null,
    TypeOfDirector         mediumtext charset utf8                                                                                null
)
    charset = latin1;

create index ClassName
    on DeletedDupeMember (ClassName);

create table Deployment
(
    ID                                            int auto_increment
        primary key,
    ClassName                                     enum ('Deployment') charset utf8 default 'Deployment' null,
    LastEdited                                    datetime                                              null,
    Created                                       datetime                                              null,
    Label                                         mediumtext charset utf8                               null,
    IsPublic                                      tinyint unsigned                 default '0'          not null,
    DeploymentType                                mediumtext charset utf8                               null,
    ProjectsUsed                                  mediumtext charset utf8                               null,
    CurrentReleases                               mediumtext charset utf8                               null,
    DeploymentStage                               mediumtext charset utf8                               null,
    NumCloudUsers                                 mediumtext charset utf8                               null,
    WorkloadsDescription                          mediumtext charset utf8                               null,
    OtherWorkloadsDescription                     mediumtext charset utf8                               null,
    APIFormats                                    mediumtext charset utf8                               null,
    Hypervisors                                   mediumtext charset utf8                               null,
    OtherHypervisor                               mediumtext charset utf8                               null,
    BlockStorageDrivers                           mediumtext charset utf8                               null,
    OtherBlockStorageDriver                       mediumtext charset utf8                               null,
    NetworkDrivers                                mediumtext charset utf8                               null,
    OtherNetworkDriver                            mediumtext charset utf8                               null,
    WhyNovaNetwork                                mediumtext charset utf8                               null,
    OtherWhyNovaNetwork                           mediumtext charset utf8                               null,
    IdentityDrivers                               mediumtext charset utf8                               null,
    OtherIndentityDriver                          mediumtext charset utf8                               null,
    SupportedFeatures                             mediumtext charset utf8                               null,
    DeploymentTools                               mediumtext charset utf8                               null,
    OtherDeploymentTools                          mediumtext charset utf8                               null,
    OperatingSystems                              mediumtext charset utf8                               null,
    OtherOperatingSystems                         mediumtext charset utf8                               null,
    ComputeNodes                                  mediumtext charset utf8                               null,
    ComputeCores                                  mediumtext charset utf8                               null,
    ComputeInstances                              mediumtext charset utf8                               null,
    BlockStorageTotalSize                         mediumtext charset utf8                               null,
    ObjectStorageSize                             mediumtext charset utf8                               null,
    ObjectStorageNumObjects                       mediumtext charset utf8                               null,
    NetworkNumIPs                                 mediumtext charset utf8                               null,
    SendDigest                                    tinyint unsigned                 default '0'          not null,
    UpdateDate                                    datetime                                              null,
    SwiftGlobalDistributionFeatures               mediumtext charset utf8                               null,
    SwiftGlobalDistributionFeaturesUsesCases      mediumtext charset utf8                               null,
    OtherSwiftGlobalDistributionFeaturesUsesCases mediumtext charset utf8                               null,
    Plans2UseSwiftStoragePolicies                 mediumtext charset utf8                               null,
    OtherPlans2UseSwiftStoragePolicies            mediumtext charset utf8                               null,
    UsedDBForOpenStackComponents                  mediumtext charset utf8                               null,
    OtherUsedDBForOpenStackComponents             mediumtext charset utf8                               null,
    ToolsUsedForYourUsers                         mediumtext charset utf8                               null,
    OtherToolsUsedForYourUsers                    mediumtext charset utf8                               null,
    Reason2Move2Ceilometer                        mediumtext charset utf8                               null,
    CountriesPhysicalLocation                     mediumtext charset utf8                               null,
    CountriesUsersLocation                        mediumtext charset utf8                               null,
    ServicesDeploymentsWorkloads                  mediumtext charset utf8                               null,
    OtherServicesDeploymentsWorkloads             mediumtext charset utf8                               null,
    EnterpriseDeploymentsWorkloads                mediumtext charset utf8                               null,
    OtherEnterpriseDeploymentsWorkloads           mediumtext charset utf8                               null,
    HorizontalWorkloadFrameworks                  mediumtext charset utf8                               null,
    OtherHorizontalWorkloadFrameworks             mediumtext charset utf8                               null,
    UsedPackages                                  mediumtext charset utf8                               null,
    CustomPackagesReason                          mediumtext charset utf8                               null,
    OtherCustomPackagesReason                     mediumtext charset utf8                               null,
    PaasTools                                     mediumtext charset utf8                               null,
    OtherPaasTools                                mediumtext charset utf8                               null,
    OtherSupportedFeatures                        mediumtext charset utf8                               null,
    InteractingClouds                             mediumtext charset utf8                               null,
    OtherInteractingClouds                        mediumtext charset utf8                               null,
    DeploymentSurveyID                            int                                                   null,
    OrgID                                         int                                                   null
)
    charset = latin1;

create index ClassName
    on Deployment (ClassName);

create index DeploymentSurveyID
    on Deployment (DeploymentSurveyID);

create index OrgID
    on Deployment (OrgID);

create table DeploymentSurvey
(
    ID                                 int auto_increment
        primary key,
    ClassName                          enum ('DeploymentSurvey') charset utf8 default 'DeploymentSurvey' null,
    LastEdited                         datetime                                                          null,
    Created                            datetime                                                          null,
    Title                              mediumtext charset utf8                                           null,
    Industry                           mediumtext charset utf8                                           null,
    OtherIndustry                      mediumtext charset utf8                                           null,
    PrimaryCity                        mediumtext charset utf8                                           null,
    PrimaryState                       mediumtext charset utf8                                           null,
    PrimaryCountry                     mediumtext charset utf8                                           null,
    OrgSize                            mediumtext charset utf8                                           null,
    OpenStackInvolvement               mediumtext charset utf8                                           null,
    InformationSources                 mediumtext charset utf8                                           null,
    OtherInformationSources            mediumtext charset utf8                                           null,
    FurtherEnhancement                 mediumtext charset utf8                                           null,
    FoundationUserCommitteePriorities  mediumtext charset utf8                                           null,
    BusinessDrivers                    mediumtext charset utf8                                           null,
    OtherBusinessDrivers               mediumtext charset utf8                                           null,
    WhatDoYouLikeMost                  mediumtext charset utf8                                           null,
    UserGroupMember                    tinyint unsigned                       default '0'                not null,
    UserGroupName                      mediumtext charset utf8                                           null,
    CurrentStep                        mediumtext charset utf8                                           null,
    HighestStepAllowed                 mediumtext charset utf8                                           null,
    BeenEmailed                        tinyint unsigned                       default '0'                not null,
    OkToContact                        tinyint unsigned                       default '0'                not null,
    SendDigest                         tinyint unsigned                       default '0'                not null,
    UpdateDate                         datetime                                                          null,
    FirstName                          mediumtext charset utf8                                           null,
    Surname                            mediumtext charset utf8                                           null,
    Email                              mediumtext charset utf8                                           null,
    OpenStackRecommendRate             mediumtext charset utf8                                           null,
    OpenStackRecommendation            mediumtext charset utf8                                           null,
    OpenStackActivity                  mediumtext charset utf8                                           null,
    OpenStackRelationship              mediumtext charset utf8                                           null,
    ITActivity                         mediumtext charset utf8                                           null,
    InterestedUsingContainerTechnology tinyint unsigned                       default '0'                not null,
    ContainerRelatedTechnologies       mediumtext charset utf8                                           null,
    MemberID                           int                                                               null,
    OrgID                              int                                                               null
)
    charset = latin1;

create index ClassName
    on DeploymentSurvey (ClassName);

create index MemberID
    on DeploymentSurvey (MemberID);

create index OrgID
    on DeploymentSurvey (OrgID);

create table Distribution
(
    ID       int auto_increment
        primary key,
    Priority varchar(5) charset utf8 null
)
    charset = latin1;

create table DoctrineMigration
(
    version     varchar(14) not null,
    executed_at datetime    null comment '(DC2Type:datetime_immutable)'
)
    collate = utf8_unicode_ci;

create table Driver
(
    ID          int auto_increment
        primary key,
    ClassName   enum ('Driver') charset utf8 default 'Driver' null,
    LastEdited  datetime                                      null,
    Created     datetime                                      null,
    Name        varchar(255) charset utf8                     null,
    Description mediumtext charset utf8                       null,
    Project     varchar(255) charset utf8                     null,
    Vendor      varchar(255) charset utf8                     null,
    Url         varchar(255) charset utf8                     null,
    Tested      tinyint unsigned             default '0'      not null,
    Active      tinyint unsigned             default '0'      not null,
    constraint Name_Project
        unique (Name, Project, Vendor)
)
    charset = latin1;

create index ClassName
    on Driver (ClassName);

create table DriverRelease
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('DriverRelease') charset utf8 default 'DriverRelease' null,
    LastEdited datetime                                                    null,
    Created    datetime                                                    null,
    Name       varchar(255) charset utf8                                   null,
    Url        varchar(255) charset utf8                                   null,
    Start      datetime                                                    null,
    Active     tinyint unsigned                    default '0'             not null,
    constraint Name
        unique (Name)
)
    charset = latin1;

create index ClassName
    on DriverRelease (ClassName);

create table Driver_Releases
(
    ID              int auto_increment
        primary key,
    DriverID        int default 0 not null,
    DriverReleaseID int default 0 not null
)
    charset = latin1;

create index DriverID
    on Driver_Releases (DriverID);

create index DriverReleaseID
    on Driver_Releases (DriverReleaseID);

create table DupeMemberActionRequest
(
    ID               int auto_increment
        primary key,
    ClassName        enum ('DupeMemberActionRequest', 'DupeMemberDeleteRequest', 'DupeMemberMergeRequest') charset utf8 default 'DupeMemberActionRequest' null,
    LastEdited       datetime                                                                                                                             null,
    Created          datetime                                                                                                                             null,
    ConfirmationHash mediumtext charset utf8                                                                                                              null,
    IsConfirmed      tinyint unsigned                                                                                   default '0'                       not null,
    ConfirmationDate datetime                                                                                                                             null,
    IsRevoked        tinyint unsigned                                                                                   default '0'                       not null,
    DupeAccountID    int                                                                                                                                  null,
    PrimaryAccountID int                                                                                                                                  null
)
    charset = latin1;

create index ClassName
    on DupeMemberActionRequest (ClassName);

create index DupeAccountID
    on DupeMemberActionRequest (DupeAccountID);

create index PrimaryAccountID
    on DupeMemberActionRequest (PrimaryAccountID);

create table Election
(
    ID                                                   int auto_increment
        primary key,
    ClassName                                            enum ('Election') charset utf8 default 'Election'                                                                                                                                                          null,
    LastEdited                                           datetime                                                                                                                                                                                                   null,
    Created                                              datetime                                                                                                                                                                                                   null,
    Name                                                 varchar(50) charset utf8                                                                                                                                                                                   null,
    NominationsOpen                                      datetime                                                                                                                                                                                                   null,
    NominationsClose                                     datetime                                                                                                                                                                                                   null,
    NominationAppDeadline                                datetime                                                                                                                                                                                                   null,
    ElectionsOpen                                        datetime                                                                                                                                                                                                   null,
    ElectionsClose                                       datetime                                                                                                                                                                                                   null,
    TimeZoneIdentifier                                   varchar(255) charset utf8                                                                                                                                                                                  null,
    VoterFileID                                          int                                                                                                                                                                                                        null,
    CandidateApplicationFormRelationshipToOpenStackLabel varchar(255)                   default 'What is your relationship to OpenStack, and why is its success important to you? What would you say is your biggest contribution to OpenStack''s success to date?' null,
    CandidateApplicationFormExperienceLabel              varchar(255)                   default 'Describe your experience with other non profits or serving as a board member. How does your experience prepare you for the role of a board member?'                null,
    CandidateApplicationFormBoardsRoleLabel              varchar(255)                   default 'What do you see as the Board''s role in OpenStack''s success?'                                                                                                     null,
    CandidateApplicationFormTopPriorityLabel             varchar(255)                   default 'What do you think the top priority of the Board should be over the next year?'                                                                                     null
)
    charset = latin1;

create index ClassName
    on Election (ClassName);

create index VoterFileID
    on Election (VoterFileID);

create table ElectionPage
(
    ID                                                   int auto_increment
        primary key,
    CandidateApplicationFormBioLabel                     mediumtext charset utf8 null,
    CandidateApplicationFormRelationshipToOpenStackLabel mediumtext charset utf8 null,
    CandidateApplicationFormExperienceLabel              mediumtext charset utf8 null,
    CandidateApplicationFormBoardsRoleLabel              mediumtext charset utf8 null,
    CandidateApplicationFormTopPriorityLabel             mediumtext charset utf8 null,
    CurrentElectionID                                    int                     null
)
    charset = latin1;

create index CurrentElectionID
    on ElectionPage (CurrentElectionID);

create table ElectionPage_Live
(
    ID                                                   int auto_increment
        primary key,
    CandidateApplicationFormBioLabel                     mediumtext charset utf8 null,
    CandidateApplicationFormRelationshipToOpenStackLabel mediumtext charset utf8 null,
    CandidateApplicationFormExperienceLabel              mediumtext charset utf8 null,
    CandidateApplicationFormBoardsRoleLabel              mediumtext charset utf8 null,
    CandidateApplicationFormTopPriorityLabel             mediumtext charset utf8 null,
    CurrentElectionID                                    int                     null
)
    charset = latin1;

create index CurrentElectionID
    on ElectionPage_Live (CurrentElectionID);

create table ElectionPage_versions
(
    ID                                                   int auto_increment
        primary key,
    RecordID                                             int default 0           not null,
    Version                                              int default 0           not null,
    CandidateApplicationFormBioLabel                     mediumtext charset utf8 null,
    CandidateApplicationFormRelationshipToOpenStackLabel mediumtext charset utf8 null,
    CandidateApplicationFormExperienceLabel              mediumtext charset utf8 null,
    CandidateApplicationFormBoardsRoleLabel              mediumtext charset utf8 null,
    CandidateApplicationFormTopPriorityLabel             mediumtext charset utf8 null,
    CurrentElectionID                                    int                     null,
    constraint RecordID_Version
        unique (RecordID, Version)
)
    charset = latin1;

create index CurrentElectionID
    on ElectionPage_versions (CurrentElectionID);

create index RecordID
    on ElectionPage_versions (RecordID);

create index Version
    on ElectionPage_versions (Version);

create table ElectionVote
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('ElectionVote') charset utf8 default 'ElectionVote' null,
    LastEdited datetime                                                  null,
    Created    datetime                                                  null,
    VoterID    int                                                       null,
    ElectionID int                                                       null
)
    charset = latin1;

create index ClassName
    on ElectionVote (ClassName);

create index ElectionID
    on ElectionVote (ElectionID);

create index VoterID
    on ElectionVote (VoterID);

create table ElectionVoterFile
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('ElectionVoterFile') charset utf8 default 'ElectionVoterFile' null,
    LastEdited datetime                                                            null,
    Created    datetime                                                            null,
    FileName   varchar(255) charset utf8                                           null,
    constraint FileName
        unique (FileName)
)
    charset = latin1;

create index ClassName
    on ElectionVoterFile (ClassName);

create table ElectionVoterPage
(
    ID             int auto_increment
        primary key,
    MustBeMemberBy date null
)
    charset = latin1;

create table ElectionVoterPage_Live
(
    ID             int auto_increment
        primary key,
    MustBeMemberBy date null
)
    charset = latin1;

create table ElectionVoterPage_versions
(
    ID             int auto_increment
        primary key,
    RecordID       int default 0 not null,
    Version        int default 0 not null,
    MustBeMemberBy date          null,
    constraint RecordID_Version
        unique (RecordID, Version)
)
    charset = latin1;

create index RecordID
    on ElectionVoterPage_versions (RecordID);

create index Version
    on ElectionVoterPage_versions (Version);

create table EmailCreationRequest
(
    ID            int auto_increment
        primary key,
    ClassName     enum ('EmailCreationRequest', 'CalendarSyncErrorEmailRequest', 'MemberPromoCodeEmailCreationRequest', 'PresentationCreatorNotificationEmailRequest', 'PresentationSpeakerNotificationEmailRequest', 'SpeakerCreationEmailCreationRequest', 'SpeakerSelectionAnnouncementEmailCreationRequest') charset utf8 default 'EmailCreationRequest' null,
    LastEdited    datetime                                                                                                                                                                                                                                                                                                                                   null,
    Created       datetime                                                                                                                                                                                                                                                                                                                                   null,
    TemplateName  varchar(100) charset utf8                                                                                                                                                                                                                                                                                                                  null,
    Processed     tinyint unsigned                                                                                                                                                                                                                                                                                            default '0'                    not null,
    ProcessedDate datetime                                                                                                                                                                                                                                                                                                                                   null
)
    charset = latin1;

create index ClassName
    on EmailCreationRequest (ClassName);

create table EntitySurvey
(
    ID         int auto_increment
        primary key,
    TemplateID int null,
    ParentID   int null,
    OwnerID    int null,
    EditedByID int null
)
    charset = latin1;

create index EditedByID
    on EntitySurvey (EditedByID);

create index OwnerID
    on EntitySurvey (OwnerID);

create index ParentID
    on EntitySurvey (ParentID);

create index TemplateID
    on EntitySurvey (TemplateID);

create table EntitySurveyTemplate
(
    ID             int auto_increment
        primary key,
    EntityName     varchar(255) charset utf8    null,
    UseTeamEdition tinyint unsigned default '0' not null,
    ParentID       int                          null,
    OwnerID        int                          null,
    constraint ParentID_EntityName
        unique (ParentID, EntityName)
)
    charset = latin1;

create index OwnerID
    on EntitySurveyTemplate (OwnerID);

create index ParentID
    on EntitySurveyTemplate (ParentID);

create table EntitySurvey_EditorTeam
(
    ID                           int auto_increment
        primary key,
    EntitySurveyID               int              default 0   not null,
    MemberID                     int              default 0   not null,
    EntitySurveyTeamMemberMailed tinyint unsigned default '0' not null
)
    charset = latin1;

create index EntitySurveyID
    on EntitySurvey_EditorTeam (EntitySurveyID);

create index MemberID
    on EntitySurvey_EditorTeam (MemberID);

create table ErrorPage
(
    ID        int auto_increment
        primary key,
    ErrorCode int default 0 not null
)
    charset = latin1;

create table ErrorPage_Live
(
    ID        int auto_increment
        primary key,
    ErrorCode int default 0 not null
)
    charset = latin1;

create table ErrorPage_versions
(
    ID        int auto_increment
        primary key,
    RecordID  int default 0 not null,
    Version   int default 0 not null,
    ErrorCode int default 0 not null,
    constraint RecordID_Version
        unique (RecordID, Version)
)
    charset = latin1;

create index RecordID
    on ErrorPage_versions (RecordID);

create index Version
    on ErrorPage_versions (Version);

create table EventAlertEmail
(
    ID                             int auto_increment
        primary key,
    ClassName                      enum ('EventAlertEmail') charset utf8 default 'EventAlertEmail' null,
    LastEdited                     datetime                                                        null,
    Created                        datetime                                                        null,
    LastEventRegistrationRequestID int                                                             null
)
    charset = latin1;

create index ClassName
    on EventAlertEmail (ClassName);

create index LastEventRegistrationRequestID
    on EventAlertEmail (LastEventRegistrationRequestID);

create table EventHolder
(
    ID                 int auto_increment
        primary key,
    BannerLink         varchar(255) charset utf8 null,
    HomePageBannerLink varchar(255) charset utf8 null,
    BannerID           int                       null,
    HomePageBannerID   int                       null
)
    charset = latin1;

create index BannerID
    on EventHolder (BannerID);

create index HomePageBannerID
    on EventHolder (HomePageBannerID);

create table EventHolder_Live
(
    ID                 int auto_increment
        primary key,
    BannerLink         varchar(255) charset utf8 null,
    HomePageBannerLink varchar(255) charset utf8 null,
    BannerID           int                       null,
    HomePageBannerID   int                       null
)
    charset = latin1;

create index BannerID
    on EventHolder_Live (BannerID);

create index HomePageBannerID
    on EventHolder_Live (HomePageBannerID);

create table EventHolder_versions
(
    ID                 int auto_increment
        primary key,
    RecordID           int default 0             not null,
    Version            int default 0             not null,
    BannerLink         varchar(255) charset utf8 null,
    HomePageBannerLink varchar(255) charset utf8 null,
    BannerID           int                       null,
    HomePageBannerID   int                       null,
    constraint RecordID_Version
        unique (RecordID, Version)
)
    charset = latin1;

create index BannerID
    on EventHolder_versions (BannerID);

create index HomePageBannerID
    on EventHolder_versions (HomePageBannerID);

create index RecordID
    on EventHolder_versions (RecordID);

create index Version
    on EventHolder_versions (Version);

create table EventPage
(
    ID                  int auto_increment
        primary key,
    ClassName           enum ('EventPage') charset utf8 default 'EventPage' null,
    LastEdited          datetime                                            null,
    Created             datetime                                            null,
    Title               varchar(255) charset utf8                           null,
    EventStartDate      date                                                null,
    EventEndDate        date                                                null,
    EventLink           mediumtext charset utf8                             null,
    EventLinkLabel      mediumtext charset utf8                             null,
    EventCategory       mediumtext charset utf8                             null,
    EventLocation       mediumtext charset utf8                             null,
    EventSponsor        mediumtext charset utf8                             null,
    EventSponsorLogoUrl mediumtext charset utf8                             null,
    IsSummit            tinyint unsigned                default '0'         not null,
    ExternalSourceId    varchar(255) charset utf8                           null,
    EventContinent      varchar(255) charset utf8                           null,
    DateString          varchar(255) charset utf8                           null
)
    charset = latin1;

create index ClassName
    on EventPage (ClassName);

create table EventRegistrationRequest
(
    ID                  int auto_increment
        primary key,
    ClassName           enum ('EventRegistrationRequest') charset utf8 default 'EventRegistrationRequest' null,
    LastEdited          datetime                                                                          null,
    Created             datetime                                                                          null,
    Title               varchar(35) charset utf8                                                          null,
    Url                 varchar(255) charset utf8                                                         null,
    Label               varchar(50) charset utf8                                                          null,
    City                varchar(100) charset utf8                                                         null,
    State               varchar(50) charset utf8                                                          null,
    Country             varchar(50) charset utf8                                                          null,
    StartDate           date                                                                              null,
    EndDate             date                                                                              null,
    PostDate            datetime                                                                          null,
    Sponsor             mediumtext charset utf8                                                           null,
    SponsorLogoUrl      varchar(255) charset utf8                                                         null,
    Lat                 decimal(9, 2)                                  default 0.00                       not null,
    Lng                 decimal(9, 2)                                  default 0.00                       not null,
    isPosted            tinyint unsigned                               default '0'                        not null,
    PointOfContactName  varchar(100) charset utf8                                                         null,
    PointOfContactEmail varchar(100) charset utf8                                                         null,
    isRejected          tinyint unsigned                               default '0'                        not null,
    Category            varchar(100) charset utf8                                                         null,
    MemberID            int                                                                               null,
    DateString          varchar(100) charset utf8                                                         null
)
    charset = latin1;

create index ClassName
    on EventRegistrationRequest (ClassName);

create index MemberID
    on EventRegistrationRequest (MemberID);

create table EventSignIn
(
    ID           int auto_increment
        primary key,
    ClassName    enum ('EventSignIn') charset utf8 default 'EventSignIn' null,
    LastEdited   datetime                                                null,
    Created      datetime                                                null,
    EmailAddress mediumtext charset utf8                                 null,
    FirstName    mediumtext charset utf8                                 null,
    LastName     mediumtext charset utf8                                 null,
    SigninPageID int                                                     null
)
    charset = latin1;

create index ClassName
    on EventSignIn (ClassName);

create index SigninPageID
    on EventSignIn (SigninPageID);

create table EventbriteAttendee
(
    ID                    int auto_increment
        primary key,
    ClassName             enum ('EventbriteAttendee') charset utf8 default 'EventbriteAttendee' null,
    LastEdited            datetime                                                              null,
    Created               datetime                                                              null,
    Email                 varchar(512) charset utf8                                             null,
    FirstName             varchar(512) charset utf8                                             null,
    LastName              varchar(512) charset utf8                                             null,
    Price                 decimal(9, 2)                            default 0.00                 not null,
    ExternalAttendeeId    varchar(255) charset utf8                                             null,
    ExternalTicketClassId varchar(255) charset utf8                                             null,
    Status                varchar(512) charset utf8                                             null,
    EventbriteOrderID     int                                                                   null
)
    charset = latin1;

create index ClassName
    on EventbriteAttendee (ClassName);

create index EventbriteOrderID
    on EventbriteAttendee (EventbriteOrderID);

create table EventbriteEvent
(
    ID              int auto_increment
        primary key,
    ClassName       enum ('EventbriteEvent') charset utf8                                     default 'EventbriteEvent' null,
    LastEdited      datetime                                                                                            null,
    Created         datetime                                                                                            null,
    EventType       enum ('ORDER_PLACED', 'EVENT_ADDED', 'EVENT_UPDATE', 'NONE') charset utf8 default 'NONE'            null,
    ApiUrl          varchar(512) charset utf8                                                                           null,
    Processed       tinyint unsigned                                                          default '0'               not null,
    ProcessedDate   datetime                                                                                            null,
    FinalStatus     varchar(255) charset utf8                                                                           null,
    ExternalOrderId varchar(255) charset utf8                                                                           null,
    SummitID        int                                                                                                 null
)
    charset = latin1;

create index ClassName
    on EventbriteEvent (ClassName);

create index SummitID
    on EventbriteEvent (SummitID);

create table ExtraQuestionType
(
    ID                int auto_increment
        primary key,
    Created           datetime                                                                                                                                  not null,
    LastEdited        datetime                                                                                                                                  not null,
    ClassName         enum ('ExtraQuestionType', 'SummitSelectionPlanExtraQuestionType', 'SummitOrderExtraQuestionType') default 'SummitOrderExtraQuestionType' null,
    Name              varchar(255)                                                                                                                              not null,
    Type              varchar(255)                                                                                                                              not null,
    Label             text                                                                                                                                      not null,
    `Order`           int                                                                                                default 1                              not null,
    Mandatory         tinyint(1)                                                                                         default 0                              not null,
    Placeholder       varchar(255)                                                                                       default ''                             null,
    MaxSelectedValues int                                                                                                default 0                              not null
)
    collate = utf8_unicode_ci;

create table ExtraQuestionAnswer
(
    ID         int auto_increment
        primary key,
    Created    datetime                                                                                                                        not null,
    LastEdited datetime                                                                                                                        not null,
    ClassName  enum ('ExtraQuestionAnswer', 'SummitOrderExtraQuestionAnswer', 'PresentationExtraQuestionAnswer') default 'ExtraQuestionAnswer' null,
    Value      text                                                                                                                            not null,
    QuestionID int                                                                                                                             null,
    constraint FK_B871C0E03F744DA2
        foreign key (QuestionID) references ExtraQuestionType (ID)
            on delete cascade
)
    collate = utf8_unicode_ci;

create index QuestionID
    on ExtraQuestionAnswer (QuestionID);

create table ExtraQuestionTypeValue
(
    ID         int auto_increment
        primary key,
    Created    datetime      not null,
    LastEdited datetime      not null,
    ClassName  varchar(255)  not null,
    Label      text          not null,
    Value      text          not null,
    `Order`    int default 1 not null,
    QuestionID int           null,
    constraint FK_DFF409E83F744DA2
        foreign key (QuestionID) references ExtraQuestionType (ID)
            on delete cascade
)
    collate = utf8_unicode_ci;

create index QuestionID
    on ExtraQuestionTypeValue (QuestionID);

create table Feature
(
    ID            int auto_increment
        primary key,
    ClassName     enum ('Feature') charset utf8 default 'Feature' null,
    LastEdited    datetime                                        null,
    Created       datetime                                        null,
    Feature       mediumtext charset utf8                         null,
    URL           mediumtext charset utf8                         null,
    Benefit       mediumtext charset utf8                         null,
    Roadmap       tinyint unsigned              default '0'       not null,
    ProductPageID int                                             null
)
    charset = latin1;

create index ClassName
    on Feature (ClassName);

create index ProductPageID
    on Feature (ProductPageID);

create table FeaturedEvent
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('FeaturedEvent') charset utf8 default 'FeaturedEvent' null,
    LastEdited datetime                                                    null,
    Created    datetime                                                    null,
    EventID    int                                                         null,
    PictureID  int                                                         null
)
    charset = latin1;

create index ClassName
    on FeaturedEvent (ClassName);

create index EventID
    on FeaturedEvent (EventID);

create index PictureID
    on FeaturedEvent (PictureID);

create table FeaturedVideo
(
    ID                         int auto_increment
        primary key,
    ClassName                  enum ('FeaturedVideo') charset utf8 default 'FeaturedVideo' null,
    LastEdited                 datetime                                                    null,
    Created                    datetime                                                    null,
    Name                       mediumtext charset utf8                                     null,
    Day                        int                                 default 0               not null,
    YouTubeID                  varchar(50) charset utf8                                    null,
    Description                mediumtext charset utf8                                     null,
    URLSegment                 mediumtext charset utf8                                     null,
    PresentationCategoryPageID int                                                         null
)
    charset = latin1;

create index ClassName
    on FeaturedVideo (ClassName);

create index PresentationCategoryPageID
    on FeaturedVideo (PresentationCategoryPageID);

create table FeedbackSubmission
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('FeedbackSubmission') charset utf8 default 'FeedbackSubmission' null,
    LastEdited datetime                                                              null,
    Created    datetime                                                              null,
    Content    mediumtext charset utf8                                               null,
    Page       mediumtext charset utf8                                               null
)
    charset = latin1;

create index ClassName
    on FeedbackSubmission (ClassName);

create table File
(
    ID            int auto_increment
        primary key,
    ClassName     enum ('File', 'Folder', 'CloudFolder', 'Image', 'Image_Cached', 'CloudImageMissing', 'CloudImage', 'AttachmentImage', 'BetterImage', 'CloudImageCached', 'MarketingImage', 'OpenStackDaysImage', 'CloudFile', 'AttachmentFile', 'MarketingFile') charset utf8 default 'File'  null,
    LastEdited    datetime                                                                                                                                                                                                                                                                      null,
    Created       datetime                                                                                                                                                                                                                                                                      null,
    Name          varchar(255) charset utf8                                                                                                                                                                                                                                                     null,
    Title         varchar(255) charset utf8                                                                                                                                                                                                                                                     null,
    Filename      mediumtext charset utf8                                                                                                                                                                                                                                                       null,
    Content       mediumtext charset utf8                                                                                                                                                                                                                                                       null,
    ShowInSearch  tinyint unsigned                                                                                                                                                                                                                                              default '1'     not null,
    CloudStatus   enum ('Local', 'Live', 'Error') charset utf8                                                                                                                                                                                                                  default 'Local' null,
    CloudSize     int                                                                                                                                                                                                                                                           default 0       not null,
    CloudMetaJson mediumtext charset utf8                                                                                                                                                                                                                                                       null,
    ParentID      int                                                                                                                                                                                                                                                                           null,
    OwnerID       int                                                                                                                                                                                                                                                                           null
)
    charset = latin1;

create index ClassName
    on File (ClassName);

create index Name
    on File (Name);

create index OwnerID
    on File (OwnerID);

create index ParentID
    on File (ParentID);

create table FileAttachmentFieldTrack
(
    ID              int auto_increment
        primary key,
    ClassName       enum ('FileAttachmentFieldTrack') charset utf8 default 'FileAttachmentFieldTrack' null,
    LastEdited      datetime                                                                          null,
    Created         datetime                                                                          null,
    ControllerClass varchar(60) charset utf8                                                          null,
    RecordID        int                                            default 0                          not null,
    RecordClass     varchar(60) charset utf8                                                          null,
    FileID          int                                                                               null
)
    charset = latin1;

create index ClassName
    on FileAttachmentFieldTrack (ClassName);

create index FileID
    on FileAttachmentFieldTrack (FileID);

create table Folder
(
    ID            int auto_increment
        primary key,
    CloudStatus   enum ('Local', 'Live', 'Error') charset utf8 default 'Local' null,
    CloudSize     int                                          default 0       not null,
    CloudMetaJson mediumtext charset utf8                                      null
)
    charset = latin1;

create table FoundationMemberRevocationNotification
(
    ID             int auto_increment
        primary key,
    ClassName      enum ('FoundationMemberRevocationNotification') charset utf8 default 'FoundationMemberRevocationNotification' null,
    LastEdited     datetime                                                                                                      null,
    Created        datetime                                                                                                      null,
    Action         enum ('None', 'Renew', 'Revoked', 'Resign') charset utf8     default 'None'                                   null,
    ActionDate     datetime                                                                                                      null,
    SentDate       datetime                                                                                                      null,
    Hash           mediumtext charset utf8                                                                                       null,
    LastElectionID int                                                                                                           null,
    RecipientID    int                                                                                                           null
)
    charset = latin1;

create index ClassName
    on FoundationMemberRevocationNotification (ClassName);

create index LastElectionID
    on FoundationMemberRevocationNotification (LastElectionID);

create index RecipientID
    on FoundationMemberRevocationNotification (RecipientID);

create table GeoCodingQuery
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('GeoCodingQuery') charset utf8 default 'GeoCodingQuery' null,
    LastEdited datetime                                                      null,
    Created    datetime                                                      null,
    Query      mediumtext charset utf8                                       null,
    Lat        decimal(9, 2)                        default 0.00             not null,
    Lng        decimal(9, 2)                        default 0.00             not null
)
    charset = latin1;

create index ClassName
    on GeoCodingQuery (ClassName);

create table GerritChangeInfo
(
    ID                int auto_increment
        primary key,
    ClassName         enum ('GerritChangeInfo') charset utf8 default 'GerritChangeInfo' null,
    LastEdited        datetime                                                          null,
    Created           datetime                                                          null,
    kind              mediumtext charset utf8                                           null,
    FormattedChangeId mediumtext charset utf8                                           null,
    ProjectName       mediumtext charset utf8                                           null,
    Branch            mediumtext charset utf8                                           null,
    Topic             mediumtext charset utf8                                           null,
    ChangeId          varchar(128) charset utf8                                         null,
    Subject           mediumtext charset utf8                                           null,
    Status            mediumtext charset utf8                                           null,
    CreatedDate       datetime                                                          null,
    UpdatedDate       datetime                                                          null,
    OwnerID           int                                                               null,
    constraint ChangeId
        unique (ChangeId)
)
    charset = latin1;

create index ClassName
    on GerritChangeInfo (ClassName);

create index OwnerID
    on GerritChangeInfo (OwnerID);

create table GerritUser
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('GerritUser') charset utf8 default 'GerritUser' null,
    LastEdited datetime                                              null,
    Created    datetime                                              null,
    AccountID  mediumtext charset utf8                               null,
    Email      mediumtext charset utf8                               null,
    MemberID   int                                                   null
)
    charset = latin1;

create index ClassName
    on GerritUser (ClassName);

create index MemberID
    on GerritUser (MemberID);

create table GitHubRepositoryConfiguration
(
    ID                              int auto_increment
        primary key,
    ClassName                       enum ('GitHubRepositoryConfiguration') charset utf8 default 'GitHubRepositoryConfiguration' null,
    LastEdited                      datetime                                                                                    null,
    Created                         datetime                                                                                    null,
    Name                            mediumtext charset utf8                                                                     null,
    WebHookSecret                   mediumtext charset utf8                                                                     null,
    RejectReasonNotMember           mediumtext charset utf8                                                                     null,
    RejectReasonNotFoundationMember mediumtext charset utf8                                                                     null,
    RejectReasonNotCCLATeam         mediumtext charset utf8                                                                     null
)
    charset = latin1;

create index ClassName
    on GitHubRepositoryConfiguration (ClassName);

create table GitHubRepositoryConfiguration_AllowedTeams
(
    ID                              int auto_increment
        primary key,
    GitHubRepositoryConfigurationID int default 0 not null,
    TeamID                          int default 0 not null
)
    charset = latin1;

create index GitHubRepositoryConfigurationID
    on GitHubRepositoryConfiguration_AllowedTeams (GitHubRepositoryConfigurationID);

create index TeamID
    on GitHubRepositoryConfiguration_AllowedTeams (TeamID);

create table GitHubRepositoryPullRequest
(
    ID                 int auto_increment
        primary key,
    ClassName          enum ('GitHubRepositoryPullRequest') charset utf8                                         default 'GitHubRepositoryPullRequest' null,
    LastEdited         datetime                                                                                                                        null,
    Created            datetime                                                                                                                        null,
    Body               mediumtext charset utf8                                                                                                         null,
    RejectReason       enum ('None', 'Approved', 'NotMember', 'NotFoundationMember', 'NotCCLATeam') charset utf8 default 'None'                        null,
    Processed          tinyint unsigned                                                                          default '0'                           not null,
    ProcessedDate      datetime                                                                                                                        null,
    GitHubRepositoryID int                                                                                                                             null
)
    charset = latin1;

create index ClassName
    on GitHubRepositoryPullRequest (ClassName);

create index GitHubRepositoryID
    on GitHubRepositoryPullRequest (GitHubRepositoryID);

create table `Group`
(
    ID               int auto_increment
        primary key,
    ClassName        enum ('Group') charset utf8 default 'Group' null,
    LastEdited       datetime                                    null,
    Created          datetime                                    null,
    Title            varchar(255) charset utf8                   null,
    Description      mediumtext charset utf8                     null,
    Code             varchar(255) charset utf8                   null,
    Locked           tinyint unsigned            default '0'     not null,
    Sort             int                         default 0       not null,
    HtmlEditorConfig mediumtext charset utf8                     null,
    ParentID         int                                         null,
    IsExternal       tinyint(1)                  default 0       not null
)
    charset = latin1;

create index ClassName
    on `Group` (ClassName);

create index ParentID
    on `Group` (ParentID);

create table Group_Members
(
    ID        int auto_increment
        primary key,
    GroupID   int default 0 not null,
    MemberID  int default 0 not null,
    SortIndex int default 0 not null
)
    charset = latin1;

create index GroupID
    on Group_Members (GroupID);

create index MemberID
    on Group_Members (MemberID);

create table Group_Roles
(
    ID               int auto_increment
        primary key,
    GroupID          int default 0 not null,
    PermissionRoleID int default 0 not null
)
    charset = latin1;

create index GroupID
    on Group_Roles (GroupID);

create index PermissionRoleID
    on Group_Roles (PermissionRoleID);

create table GuestOSType
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('GuestOSType') charset utf8 default 'GuestOSType' null,
    LastEdited datetime                                                null,
    Created    datetime                                                null,
    Type       varchar(50) charset utf8                                null,
    constraint Type
        unique (Type)
)
    charset = latin1;

create index ClassName
    on GuestOSType (ClassName);

create table HackathonsPage
(
    ID               int auto_increment
        primary key,
    AboutDescription mediumtext charset utf8 null,
    HostIntro        mediumtext charset utf8 null,
    HostFAQs         mediumtext charset utf8 null,
    ToolkitDesc      mediumtext charset utf8 null,
    ArtworkIntro     mediumtext charset utf8 null,
    CollateralIntro  mediumtext charset utf8 null
)
    charset = latin1;

create table HackathonsPage_Live
(
    ID               int auto_increment
        primary key,
    AboutDescription mediumtext charset utf8 null,
    HostIntro        mediumtext charset utf8 null,
    HostFAQs         mediumtext charset utf8 null,
    ToolkitDesc      mediumtext charset utf8 null,
    ArtworkIntro     mediumtext charset utf8 null,
    CollateralIntro  mediumtext charset utf8 null
)
    charset = latin1;

create table HackathonsPage_versions
(
    ID               int auto_increment
        primary key,
    RecordID         int default 0           not null,
    Version          int default 0           not null,
    AboutDescription mediumtext charset utf8 null,
    HostIntro        mediumtext charset utf8 null,
    HostFAQs         mediumtext charset utf8 null,
    ToolkitDesc      mediumtext charset utf8 null,
    ArtworkIntro     mediumtext charset utf8 null,
    CollateralIntro  mediumtext charset utf8 null,
    constraint RecordID_Version
        unique (RecordID, Version)
)
    charset = latin1;

create index RecordID
    on HackathonsPage_versions (RecordID);

create index Version
    on HackathonsPage_versions (Version);

create table HomePage
(
    ID                        int auto_increment
        primary key,
    FeedData                  mediumtext charset utf8      null,
    EventDate                 date                         null,
    VideoCurrentlyPlaying     mediumtext charset utf8      null,
    PromoIntroMessage         mediumtext charset utf8      null,
    PromoIntroSize            varchar(20) charset utf8     null,
    PromoButtonText           mediumtext charset utf8      null,
    PromoButtonUrl            mediumtext charset utf8      null,
    PromoDatesText            mediumtext charset utf8      null,
    PromoDatesSize            varchar(20) charset utf8     null,
    PromoHeroCredit           mediumtext charset utf8      null,
    PromoHeroCreditUrl        mediumtext charset utf8      null,
    SummitMode                tinyint unsigned default '0' not null,
    NextPresentationStartTime mediumtext charset utf8      null,
    NextPresentationStartDate mediumtext charset utf8      null,
    LiveStreamURL             mediumtext charset utf8      null,
    PromoImageID              int                          null
)
    charset = latin1;

create index PromoImageID
    on HomePage (PromoImageID);

create table HomePage_Live
(
    ID                        int auto_increment
        primary key,
    FeedData                  mediumtext charset utf8      null,
    EventDate                 date                         null,
    VideoCurrentlyPlaying     mediumtext charset utf8      null,
    PromoIntroMessage         mediumtext charset utf8      null,
    PromoIntroSize            varchar(20) charset utf8     null,
    PromoButtonText           mediumtext charset utf8      null,
    PromoButtonUrl            mediumtext charset utf8      null,
    PromoDatesText            mediumtext charset utf8      null,
    PromoDatesSize            varchar(20) charset utf8     null,
    PromoHeroCredit           mediumtext charset utf8      null,
    PromoHeroCreditUrl        mediumtext charset utf8      null,
    SummitMode                tinyint unsigned default '0' not null,
    NextPresentationStartTime mediumtext charset utf8      null,
    NextPresentationStartDate mediumtext charset utf8      null,
    LiveStreamURL             mediumtext charset utf8      null,
    PromoImageID              int                          null
)
    charset = latin1;

create index PromoImageID
    on HomePage_Live (PromoImageID);

create table HomePage_versions
(
    ID                        int auto_increment
        primary key,
    RecordID                  int              default 0   not null,
    Version                   int              default 0   not null,
    FeedData                  mediumtext charset utf8      null,
    EventDate                 date                         null,
    VideoCurrentlyPlaying     mediumtext charset utf8      null,
    PromoIntroMessage         mediumtext charset utf8      null,
    PromoIntroSize            varchar(20) charset utf8     null,
    PromoButtonText           mediumtext charset utf8      null,
    PromoButtonUrl            mediumtext charset utf8      null,
    PromoDatesText            mediumtext charset utf8      null,
    PromoDatesSize            varchar(20) charset utf8     null,
    PromoHeroCredit           mediumtext charset utf8      null,
    PromoHeroCreditUrl        mediumtext charset utf8      null,
    SummitMode                tinyint unsigned default '0' not null,
    NextPresentationStartTime mediumtext charset utf8      null,
    NextPresentationStartDate mediumtext charset utf8      null,
    LiveStreamURL             mediumtext charset utf8      null,
    PromoImageID              int                          null,
    constraint RecordID_Version
        unique (RecordID, Version)
)
    charset = latin1;

create index PromoImageID
    on HomePage_versions (PromoImageID);

create index RecordID
    on HomePage_versions (RecordID);

create index Version
    on HomePage_versions (Version);

create table HyperVisorType
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('HyperVisorType') charset utf8 default 'HyperVisorType' null,
    LastEdited datetime                                                      null,
    Created    datetime                                                      null,
    Type       varchar(50) charset utf8                                      null,
    constraint Type
        unique (Type)
)
    charset = latin1;

create index ClassName
    on HyperVisorType (ClassName);

create table IndexItem
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('IndexItem') charset utf8 default 'IndexItem' null,
    LastEdited datetime                                            null,
    Created    datetime                                            null,
    Title      mediumtext charset utf8                             null,
    Link       mediumtext charset utf8                             null,
    Content    mediumtext charset utf8                             null,
    `Order`    int                             default 0           not null,
    SectionID  int                                                 null
)
    charset = latin1;

create index ClassName
    on IndexItem (ClassName);

create index SectionID
    on IndexItem (SectionID);

create table InteropCapability
(
    ID          int auto_increment
        primary key,
    ClassName   enum ('InteropCapability') charset utf8    default 'InteropCapability' null,
    LastEdited  datetime                                                               null,
    Created     datetime                                                               null,
    Name        varchar(50) charset utf8                                               null,
    Description mediumtext charset utf8                                                null,
    Status      enum ('Required', 'Advisory') charset utf8 default 'Required'          null,
    TypeID      int                                                                    null
)
    charset = latin1;

create index ClassName
    on InteropCapability (ClassName);

create index TypeID
    on InteropCapability (TypeID);

create table InteropCapabilityType
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('InteropCapabilityType') charset utf8 default 'InteropCapabilityType' null,
    LastEdited datetime                                                                    null,
    Created    datetime                                                                    null,
    Name       varchar(50) charset utf8                                                    null
)
    charset = latin1;

create index ClassName
    on InteropCapabilityType (ClassName);

create table InteropDesignatedSection
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('InteropDesignatedSection') charset utf8                                       default 'InteropDesignatedSection' null,
    LastEdited datetime                                                                                                                null,
    Created    datetime                                                                                                                null,
    Name       varchar(50) charset utf8                                                                                                null,
    Comment    mediumtext charset utf8                                                                                                 null,
    Guidance   mediumtext charset utf8                                                                                                 null,
    Status     enum ('Required', 'Advisory', 'Deprecated', 'Removed', 'Informational') charset utf8 default 'Required'                 null
)
    charset = latin1;

create index ClassName
    on InteropDesignatedSection (ClassName);

create table InteropProgramType
(
    ID              int auto_increment
        primary key,
    ClassName       enum ('InteropProgramType') charset utf8 default 'InteropProgramType' null,
    LastEdited      datetime                                                              null,
    Created         datetime                                                              null,
    Name            varchar(50) charset utf8                                              null,
    ShortName       varchar(50) charset utf8                                              null,
    `Order`         int                                      default 0                    not null,
    RequiredCode    mediumtext charset utf8                                               null,
    ProductExamples mediumtext charset utf8                                               null,
    TrademarkUse    mediumtext charset utf8                                               null,
    HasCapabilities tinyint unsigned                         default '0'                  not null
)
    charset = latin1;

create index ClassName
    on InteropProgramType (ClassName);

create table InteropProgramType_Capabilities
(
    ID                   int auto_increment
        primary key,
    InteropProgramTypeID int default 0 not null,
    InteropCapabilityID  int default 0 not null
)
    charset = latin1;

create index InteropCapabilityID
    on InteropProgramType_Capabilities (InteropCapabilityID);

create index InteropProgramTypeID
    on InteropProgramType_Capabilities (InteropProgramTypeID);

create table InteropProgramType_DesignatedSections
(
    ID                         int auto_increment
        primary key,
    InteropProgramTypeID       int default 0 not null,
    InteropDesignatedSectionID int default 0 not null
)
    charset = latin1;

create index InteropDesignatedSectionID
    on InteropProgramType_DesignatedSections (InteropDesignatedSectionID);

create index InteropProgramTypeID
    on InteropProgramType_DesignatedSections (InteropProgramTypeID);

create table InteropProgramVersion
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('InteropProgramVersion') charset utf8 default 'InteropProgramVersion' null,
    LastEdited datetime                                                                    null,
    Created    datetime                                                                    null,
    Name       varchar(50) charset utf8                                                    null
)
    charset = latin1;

create index ClassName
    on InteropProgramVersion (ClassName);

create table InteropProgramVersion_Capabilities
(
    ID                      int auto_increment
        primary key,
    InteropProgramVersionID int default 0 not null,
    InteropCapabilityID     int default 0 not null,
    `Order`                 int default 0 not null
)
    charset = latin1;

create index InteropCapabilityID
    on InteropProgramVersion_Capabilities (InteropCapabilityID);

create index InteropProgramVersionID
    on InteropProgramVersion_Capabilities (InteropProgramVersionID);

create table InteropProgramVersion_DesignatedSections
(
    ID                         int auto_increment
        primary key,
    InteropProgramVersionID    int default 0 not null,
    InteropDesignatedSectionID int default 0 not null,
    `Order`                    int default 0 not null
)
    charset = latin1;

create index InteropDesignatedSectionID
    on InteropProgramVersion_DesignatedSections (InteropDesignatedSectionID);

create index InteropProgramVersionID
    on InteropProgramVersion_DesignatedSections (InteropProgramVersionID);

create table InvolvementType
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('InvolvementType') charset utf8 default 'InvolvementType' null,
    LastEdited datetime                                                        null,
    Created    datetime                                                        null,
    Name       mediumtext charset utf8                                         null
)
    charset = latin1;

create index ClassName
    on InvolvementType (ClassName);

create table JSONMember
(
    ID              int auto_increment
        primary key,
    ClassName       enum ('JSONMember') charset utf8 default 'JSONMember' null,
    LastEdited      datetime                                              null,
    Created         datetime                                              null,
    FirstName       mediumtext charset utf8                               null,
    Surname         mediumtext charset utf8                               null,
    IRCHandle       mediumtext charset utf8                               null,
    TwitterName     mediumtext charset utf8                               null,
    Email           mediumtext charset utf8                               null,
    SecondEmail     mediumtext charset utf8                               null,
    ThirdEmail      mediumtext charset utf8                               null,
    OrgAffiliations mediumtext charset utf8                               null,
    untilDate       date                                                  null
)
    charset = latin1;

create index ClassName
    on JSONMember (ClassName);

create table Job
(
    ID                    int auto_increment
        primary key,
    ClassName             enum ('Job') charset utf8                      default 'Job'     null,
    LastEdited            datetime                                                         null,
    Created               datetime                                                         null,
    Title                 varchar(255) charset utf8                                        null,
    Description           mediumtext charset utf8                                          null,
    PostedDate            datetime                                                         null,
    ExpirationDate        datetime                                                         null,
    CompanyName           mediumtext charset utf8                                          null,
    MoreInfoLink          mediumtext charset utf8                                          null,
    Location              mediumtext charset utf8                                          null,
    IsFoundationJob       tinyint unsigned                               default '0'       not null,
    IsActive              tinyint unsigned                               default '0'       not null,
    Instructions2Apply    mediumtext charset utf8                                          null,
    LocationType          enum ('N/A', 'Remote', 'Various') charset utf8 default 'Various' null,
    IsCOANeeded           tinyint unsigned                               default '0'       not null,
    CompanyID             int                                                              null,
    TypeID                int                                                              null,
    RegistrationRequestID int                                                              null
)
    charset = latin1;

create index ClassName
    on Job (ClassName);

create index CompanyID
    on Job (CompanyID);

create index RegistrationRequestID
    on Job (RegistrationRequestID);

create index TypeID
    on Job (TypeID);

create table JobAlertEmail
(
    ID                           int auto_increment
        primary key,
    ClassName                    enum ('JobAlertEmail') charset utf8 default 'JobAlertEmail' null,
    LastEdited                   datetime                                                    null,
    Created                      datetime                                                    null,
    LastJobRegistrationRequestID int                                                         null
)
    charset = latin1;

create index ClassName
    on JobAlertEmail (ClassName);

create index LastJobRegistrationRequestID
    on JobAlertEmail (LastJobRegistrationRequestID);

create table JobLocation
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('JobLocation') charset utf8 default 'JobLocation' null,
    LastEdited datetime                                                null,
    Created    datetime                                                null,
    City       mediumtext charset utf8                                 null,
    State      mediumtext charset utf8                                 null,
    Country    mediumtext charset utf8                                 null,
    JobID      int                                                     null,
    RequestID  int                                                     null
)
    charset = latin1;

create index ClassName
    on JobLocation (ClassName);

create index JobID
    on JobLocation (JobID);

create index RequestID
    on JobLocation (RequestID);

create table JobRegistrationRequest
(
    ID                  int auto_increment
        primary key,
    ClassName           enum ('JobRegistrationRequest') charset utf8   default 'JobRegistrationRequest' null,
    LastEdited          datetime                                                                        null,
    Created             datetime                                                                        null,
    Title               varchar(100) charset utf8                                                       null,
    Url                 varchar(255) charset utf8                                                       null,
    CompanyName         varchar(255) charset utf8                                                       null,
    Description         mediumtext charset utf8                                                         null,
    Instructions2Apply  mediumtext charset utf8                                                         null,
    ExpirationDate      datetime                                                                        null,
    PointOfContactName  varchar(100) charset utf8                                                       null,
    PointOfContactEmail varchar(100) charset utf8                                                       null,
    PostDate            datetime                                                                        null,
    isPosted            tinyint unsigned                               default '0'                      not null,
    isRejected          tinyint unsigned                               default '0'                      not null,
    LocationType        enum ('N/A', 'Remote', 'Various') charset utf8 default 'N/A'                    null,
    City                varchar(100) charset utf8                                                       null,
    State               varchar(50) charset utf8                                                        null,
    Country             varchar(50) charset utf8                                                        null,
    IsCOANeeded         tinyint unsigned                               default '0'                      not null,
    MemberID            int                                                                             null,
    CompanyID           int                                                                             null,
    TypeID              int                                                                             null
)
    charset = latin1;

create index ClassName
    on JobRegistrationRequest (ClassName);

create index CompanyID
    on JobRegistrationRequest (CompanyID);

create index MemberID
    on JobRegistrationRequest (MemberID);

create index TypeID
    on JobRegistrationRequest (TypeID);

create table JobType
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('JobType') charset utf8 default 'JobType' null,
    LastEdited datetime                                        null,
    Created    datetime                                        null,
    Type       varchar(255) charset utf8                       null
)
    charset = latin1;

create index ClassName
    on JobType (ClassName);

create table Language
(
    ID            int auto_increment
        primary key,
    ClassName     enum ('Language') charset utf8 default 'Language' null,
    LastEdited    datetime                                          null,
    Created       datetime                                          null,
    Name          varchar(255) charset utf8                         null,
    IsoCode_639_1 varchar(2) charset utf8                           null
)
    charset = latin1;

create index ClassName
    on Language (ClassName);

create table LegalAgreement
(
    ID                  int auto_increment
        primary key,
    ClassName           enum ('LegalAgreement') charset utf8 default 'LegalAgreement' null,
    LastEdited          datetime                                                      null,
    Created             datetime                                                      null,
    Signature           varchar(255) charset utf8                                     null,
    LegalDocumentPageID int                                                           null,
    MemberID            int                                                           null
)
    charset = latin1;

create index ClassName
    on LegalAgreement (ClassName);

create index LegalDocumentPageID
    on LegalAgreement (LegalDocumentPageID);

create index MemberID
    on LegalAgreement (MemberID);

create table LegalDocumentPage
(
    ID                  int auto_increment
        primary key,
    LegalDocumentFileID int null
)
    charset = latin1;

create index LegalDocumentFileID
    on LegalDocumentPage (LegalDocumentFileID);

create table LegalDocumentPage_Live
(
    ID                  int auto_increment
        primary key,
    LegalDocumentFileID int null
)
    charset = latin1;

create index LegalDocumentFileID
    on LegalDocumentPage_Live (LegalDocumentFileID);

create table LegalDocumentPage_versions
(
    ID                  int auto_increment
        primary key,
    RecordID            int default 0 not null,
    Version             int default 0 not null,
    LegalDocumentFileID int           null,
    constraint RecordID_Version
        unique (RecordID, Version)
)
    charset = latin1;

create index LegalDocumentFileID
    on LegalDocumentPage_versions (LegalDocumentFileID);

create index RecordID
    on LegalDocumentPage_versions (RecordID);

create index Version
    on LegalDocumentPage_versions (Version);

create table Link
(
    ID          int auto_increment
        primary key,
    ClassName   enum ('Link', 'PageLink', 'OpenStackComponentLink') charset utf8 default 'Link' null,
    LastEdited  datetime                                                                        null,
    Created     datetime                                                                        null,
    Label       mediumtext charset utf8                                                         null,
    URL         mediumtext charset utf8                                                         null,
    IconClass   varchar(50) charset utf8                                                        null,
    ButtonColor varchar(6) charset utf8                                                         null,
    Description mediumtext charset utf8                                                         null
)
    charset = latin1;

create index ClassName
    on Link (ClassName);

create table LoginAttempt
(
    ID          int auto_increment
        primary key,
    ClassName   enum ('LoginAttempt') charset utf8       default 'LoginAttempt' null,
    LastEdited  datetime                                                        null,
    Created     datetime                                                        null,
    Email       varchar(255) charset utf8                                       null,
    EmailHashed varchar(255) charset utf8                                       null,
    Status      enum ('Success', 'Failure') charset utf8 default 'Success'      null,
    IP          varchar(255) charset utf8                                       null,
    MemberID    int                                                             null
)
    charset = latin1;

create index ClassName
    on LoginAttempt (ClassName);

create index MemberID
    on LoginAttempt (MemberID);

create table LogoGuidelinesPage
(
    ID           int auto_increment
        primary key,
    Preamble     mediumtext charset utf8 null,
    TrademarkURL mediumtext charset utf8 null
)
    charset = latin1;

create table LogoGuidelinesPage_Live
(
    ID           int auto_increment
        primary key,
    Preamble     mediumtext charset utf8 null,
    TrademarkURL mediumtext charset utf8 null
)
    charset = latin1;

create table LogoGuidelinesPage_versions
(
    ID           int auto_increment
        primary key,
    RecordID     int default 0           not null,
    Version      int default 0           not null,
    Preamble     mediumtext charset utf8 null,
    TrademarkURL mediumtext charset utf8 null,
    constraint RecordID_Version
        unique (RecordID, Version)
)
    charset = latin1;

create index RecordID
    on LogoGuidelinesPage_versions (RecordID);

create index Version
    on LogoGuidelinesPage_versions (Version);

create table LogoRightsPage
(
    ID             int auto_increment
        primary key,
    LogoURL        mediumtext charset utf8 null,
    AllowedMembers mediumtext charset utf8 null,
    EchoSignCode   mediumtext charset utf8 null
)
    charset = latin1;

create table LogoRightsPage_Live
(
    ID             int auto_increment
        primary key,
    LogoURL        mediumtext charset utf8 null,
    AllowedMembers mediumtext charset utf8 null,
    EchoSignCode   mediumtext charset utf8 null
)
    charset = latin1;

create table LogoRightsPage_versions
(
    ID             int auto_increment
        primary key,
    RecordID       int default 0           not null,
    Version        int default 0           not null,
    LogoURL        mediumtext charset utf8 null,
    AllowedMembers mediumtext charset utf8 null,
    EchoSignCode   mediumtext charset utf8 null,
    constraint RecordID_Version
        unique (RecordID, Version)
)
    charset = latin1;

create index RecordID
    on LogoRightsPage_versions (RecordID);

create index Version
    on LogoRightsPage_versions (Version);

create table LogoRightsSubmission
(
    ID               int auto_increment
        primary key,
    ClassName        enum ('LogoRightsSubmission') charset utf8 default 'LogoRightsSubmission' null,
    LastEdited       datetime                                                                  null,
    Created          datetime                                                                  null,
    Name             mediumtext charset utf8                                                   null,
    Email            mediumtext charset utf8                                                   null,
    PhoneNumber      mediumtext charset utf8                                                   null,
    ProductName      mediumtext charset utf8                                                   null,
    CompanyName      mediumtext charset utf8                                                   null,
    Website          mediumtext charset utf8                                                   null,
    StreetAddress    mediumtext charset utf8                                                   null,
    State            mediumtext charset utf8                                                   null,
    City             mediumtext charset utf8                                                   null,
    Country          mediumtext charset utf8                                                   null,
    Zip              mediumtext charset utf8                                                   null,
    BehalfOfCompany  tinyint unsigned                           default '0'                    not null,
    LogoRightsPageID int                                                                       null
)
    charset = latin1;

create index ClassName
    on LogoRightsSubmission (ClassName);

create index LogoRightsPageID
    on LogoRightsSubmission (LogoRightsPageID);

create table MarketPlaceAllowedInstance
(
    ID                int auto_increment
        primary key,
    ClassName         enum ('MarketPlaceAllowedInstance') charset utf8 default 'MarketPlaceAllowedInstance' null,
    LastEdited        datetime                                                                              null,
    Created           datetime                                                                              null,
    MaxInstances      int                                              default 0                            not null,
    MarketPlaceTypeID int                                                                                   null,
    CompanyID         int                                                                                   null,
    constraint Type
        unique (MarketPlaceTypeID, CompanyID)
)
    charset = latin1;

create index ClassName
    on MarketPlaceAllowedInstance (ClassName);

create index CompanyID
    on MarketPlaceAllowedInstance (CompanyID);

create index MarketPlaceTypeID
    on MarketPlaceAllowedInstance (MarketPlaceTypeID);

create table MarketPlaceDirectoryPage
(
    ID                   int auto_increment
        primary key,
    GAConversionId       mediumtext charset utf8      null,
    GAConversionLanguage mediumtext charset utf8      null,
    GAConversionFormat   mediumtext charset utf8      null,
    GAConversionColor    mediumtext charset utf8      null,
    GAConversionLabel    mediumtext charset utf8      null,
    GAConversionValue    int              default 0   not null,
    GARemarketingOnly    tinyint unsigned default '0' not null,
    RatingCompanyID      int              default 0   not null,
    RatingBoxID          int              default 0   not null
)
    charset = latin1;

create table MarketPlaceDirectoryPage_Live
(
    ID                   int auto_increment
        primary key,
    GAConversionId       mediumtext charset utf8      null,
    GAConversionLanguage mediumtext charset utf8      null,
    GAConversionFormat   mediumtext charset utf8      null,
    GAConversionColor    mediumtext charset utf8      null,
    GAConversionLabel    mediumtext charset utf8      null,
    GAConversionValue    int              default 0   not null,
    GARemarketingOnly    tinyint unsigned default '0' not null,
    RatingCompanyID      int              default 0   not null,
    RatingBoxID          int              default 0   not null
)
    charset = latin1;

create table MarketPlaceDirectoryPage_versions
(
    ID                   int auto_increment
        primary key,
    RecordID             int              default 0   not null,
    Version              int              default 0   not null,
    GAConversionId       mediumtext charset utf8      null,
    GAConversionLanguage mediumtext charset utf8      null,
    GAConversionFormat   mediumtext charset utf8      null,
    GAConversionColor    mediumtext charset utf8      null,
    GAConversionLabel    mediumtext charset utf8      null,
    GAConversionValue    int              default 0   not null,
    GARemarketingOnly    tinyint unsigned default '0' not null,
    RatingCompanyID      int              default 0   not null,
    RatingBoxID          int              default 0   not null,
    constraint RecordID_Version
        unique (RecordID, Version)
)
    charset = latin1;

create index RecordID
    on MarketPlaceDirectoryPage_versions (RecordID);

create index Version
    on MarketPlaceDirectoryPage_versions (Version);

create table MarketPlaceHelpLink
(
    ID                int auto_increment
        primary key,
    ClassName         enum ('MarketPlaceHelpLink') charset utf8 default 'MarketPlaceHelpLink' null,
    LastEdited        datetime                                                                null,
    Created           datetime                                                                null,
    Label             varchar(255) charset utf8                                               null,
    Link              varchar(255) charset utf8                                               null,
    SortOrder         int                                       default 0                     not null,
    MarketPlacePageID int                                                                     null
)
    charset = latin1;

create index ClassName
    on MarketPlaceHelpLink (ClassName);

create index MarketPlacePageID
    on MarketPlaceHelpLink (MarketPlacePageID);

create index SortOrder
    on MarketPlaceHelpLink (SortOrder);

create table MarketPlaceReview
(
    ID               int auto_increment
        primary key,
    ClassName        enum ('MarketPlaceReview') charset utf8 default 'MarketPlaceReview' null,
    LastEdited       datetime                                                            null,
    Created          datetime                                                            null,
    Title            varchar(50) charset utf8                                            null,
    Comment          mediumtext charset utf8                                             null,
    Rating           float                                   default 0                   not null,
    Approved         tinyint unsigned                        default '0'                 not null,
    MemberID         int                                                                 null,
    CompanyServiceID int                                                                 null
)
    charset = latin1;

create index ClassName
    on MarketPlaceReview (ClassName);

create index CompanyServiceID
    on MarketPlaceReview (CompanyServiceID);

create index MemberID
    on MarketPlaceReview (MemberID);

create table MarketPlaceType
(
    ID           int auto_increment
        primary key,
    ClassName    enum ('MarketPlaceType') charset utf8 default 'MarketPlaceType' null,
    LastEdited   datetime                                                        null,
    Created      datetime                                                        null,
    Name         varchar(50) charset utf8                                        null,
    Slug         varchar(50) charset utf8                                        null,
    Active       tinyint unsigned                      default '0'               not null,
    AdminGroupID int                                                             null,
    constraint Name
        unique (Name),
    constraint Slug
        unique (Slug)
)
    charset = latin1;

create index AdminGroupID
    on MarketPlaceType (AdminGroupID);

create index ClassName
    on MarketPlaceType (ClassName);

create table MarketPlaceVideo
(
    ID          int auto_increment
        primary key,
    ClassName   enum ('MarketPlaceVideo') charset utf8 default 'MarketPlaceVideo' null,
    LastEdited  datetime                                                          null,
    Created     datetime                                                          null,
    Name        mediumtext charset utf8                                           null,
    Description mediumtext charset utf8                                           null,
    YouTubeID   mediumtext charset utf8                                           null,
    Length      int                                    default 0                  not null,
    TypeID      int                                                               null,
    OwnerID     int                                                               null
)
    charset = latin1;

create index ClassName
    on MarketPlaceVideo (ClassName);

create index OwnerID
    on MarketPlaceVideo (OwnerID);

create index TypeID
    on MarketPlaceVideo (TypeID);

create table MarketPlaceVideoDraft
(
    ID          int auto_increment
        primary key,
    ClassName   enum ('MarketPlaceVideoDraft') charset utf8 default 'MarketPlaceVideoDraft' null,
    LastEdited  datetime                                                                    null,
    Created     datetime                                                                    null,
    Name        mediumtext charset utf8                                                     null,
    Description mediumtext charset utf8                                                     null,
    YouTubeID   mediumtext charset utf8                                                     null,
    Length      int                                         default 0                       not null,
    TypeID      int                                                                         null,
    OwnerID     int                                                                         null
)
    charset = latin1;

create index ClassName
    on MarketPlaceVideoDraft (ClassName);

create index OwnerID
    on MarketPlaceVideoDraft (OwnerID);

create index TypeID
    on MarketPlaceVideoDraft (TypeID);

create table MarketPlaceVideoType
(
    ID                int auto_increment
        primary key,
    ClassName         enum ('MarketPlaceVideoType') charset utf8 default 'MarketPlaceVideoType' null,
    LastEdited        datetime                                                                  null,
    Created           datetime                                                                  null,
    Type              varchar(50) charset utf8                                                  null,
    Title             mediumtext charset utf8                                                   null,
    Description       mediumtext charset utf8                                                   null,
    MaxTotalVideoTime int                                        default 0                      not null,
    constraint Type
        unique (Type)
)
    charset = latin1;

create index ClassName
    on MarketPlaceVideoType (ClassName);

create table MarketingCollateral
(
    ID           int auto_increment
        primary key,
    ClassName    enum ('MarketingCollateral') charset utf8 default 'MarketingCollateral' null,
    LastEdited   datetime                                                                null,
    Created      datetime                                                                null,
    Name         varchar(255) charset utf8                                               null,
    Description  mediumtext charset utf8                                                 null,
    ShowGlobe    tinyint unsigned                          default '0'                   not null,
    SortOrder    int                                       default 0                     not null,
    ParentPageID int                                                                     null,
    ImageID      int                                                                     null
)
    charset = latin1;

create index ClassName
    on MarketingCollateral (ClassName);

create index ImageID
    on MarketingCollateral (ImageID);

create index ParentPageID
    on MarketingCollateral (ParentPageID);

create table MarketingDoc
(
    ID           int auto_increment
        primary key,
    ClassName    enum ('MarketingDoc') charset utf8 default 'MarketingDoc' null,
    LastEdited   datetime                                                  null,
    Created      datetime                                                  null,
    Label        varchar(255) charset utf8                                 null,
    GroupName    varchar(255) charset utf8                                 null,
    SortOrder    int                                default 0              not null,
    StickersID   int                                                       null,
    TShirtsID    int                                                       null,
    BannersID    int                                                       null,
    TemplatesID  int                                                       null,
    ThumbnailID  int                                                       null,
    DocID        int                                                       null,
    ParentPageID int                                                       null
)
    charset = latin1;

create index BannersID
    on MarketingDoc (BannersID);

create index ClassName
    on MarketingDoc (ClassName);

create index DocID
    on MarketingDoc (DocID);

create index ParentPageID
    on MarketingDoc (ParentPageID);

create index StickersID
    on MarketingDoc (StickersID);

create index TShirtsID
    on MarketingDoc (TShirtsID);

create index TemplatesID
    on MarketingDoc (TemplatesID);

create index ThumbnailID
    on MarketingDoc (ThumbnailID);

create table MarketingEvent
(
    ID              int auto_increment
        primary key,
    ClassName       enum ('MarketingEvent') charset utf8 default 'MarketingEvent' null,
    LastEdited      datetime                                                      null,
    Created         datetime                                                      null,
    Title           varchar(255) charset utf8                                     null,
    Description     mediumtext charset utf8                                       null,
    ButtonLink      varchar(255) charset utf8                                     null,
    ButtonLabel     varchar(255) charset utf8                                     null,
    SortOrder       int                                  default 0                not null,
    SponsorEventsID int                                                           null,
    PromoteEventsID int                                                           null,
    ImageID         int                                                           null,
    ParentPageID    int                                                           null
)
    charset = latin1;

create index ClassName
    on MarketingEvent (ClassName);

create index ImageID
    on MarketingEvent (ImageID);

create index ParentPageID
    on MarketingEvent (ParentPageID);

create index PromoteEventsID
    on MarketingEvent (PromoteEventsID);

create index SortOrder
    on MarketingEvent (SortOrder);

create index SponsorEventsID
    on MarketingEvent (SponsorEventsID);

create table MarketingFile
(
    ID                int auto_increment
        primary key,
    SortOrder         int default 0             not null,
    `Group`           varchar(255) charset utf8 null,
    CollateralFilesID int                       null
)
    charset = latin1;

create index CollateralFilesID
    on MarketingFile (CollateralFilesID);

create table MarketingImage
(
    ID               int auto_increment
        primary key,
    SortOrder        int default 0           not null,
    Caption          mediumtext charset utf8 null,
    InvolvedImagesID int                     null,
    PromoteImagesID  int                     null,
    ParentPageID     int                     null
)
    charset = latin1;

create index InvolvedImagesID
    on MarketingImage (InvolvedImagesID);

create index ParentPageID
    on MarketingImage (ParentPageID);

create index PromoteImagesID
    on MarketingImage (PromoteImagesID);

create table MarketingLink
(
    ID           int auto_increment
        primary key,
    ClassName    enum ('MarketingLink') charset utf8 default 'MarketingLink' null,
    LastEdited   datetime                                                    null,
    Created      datetime                                                    null,
    Title        varchar(255) charset utf8                                   null,
    Link         mediumtext charset utf8                                     null,
    `Group`      varchar(255) charset utf8                                   null,
    SortOrder    int                                 default 0               not null,
    CollateralID int                                                         null
)
    charset = latin1;

create index ClassName
    on MarketingLink (ClassName);

create index CollateralID
    on MarketingLink (CollateralID);

create table MarketingPage
(
    ID                      int auto_increment
        primary key,
    HeaderTitle             varchar(255) charset utf8 null,
    HeaderText              mediumtext charset utf8   null,
    InvolvedText            mediumtext charset utf8   null,
    EventsIntroText         mediumtext charset utf8   null,
    CollateralIntroText     mediumtext charset utf8   null,
    SoftwareIntroText       mediumtext charset utf8   null,
    GraphicsIntroText       mediumtext charset utf8   null,
    PromoteProductIntroText mediumtext charset utf8   null
)
    charset = latin1;

create table MarketingPage_Live
(
    ID                      int auto_increment
        primary key,
    HeaderTitle             varchar(255) charset utf8 null,
    HeaderText              mediumtext charset utf8   null,
    InvolvedText            mediumtext charset utf8   null,
    EventsIntroText         mediumtext charset utf8   null,
    CollateralIntroText     mediumtext charset utf8   null,
    SoftwareIntroText       mediumtext charset utf8   null,
    GraphicsIntroText       mediumtext charset utf8   null,
    PromoteProductIntroText mediumtext charset utf8   null
)
    charset = latin1;

create table MarketingPage_versions
(
    ID                      int auto_increment
        primary key,
    RecordID                int default 0             not null,
    Version                 int default 0             not null,
    HeaderTitle             varchar(255) charset utf8 null,
    HeaderText              mediumtext charset utf8   null,
    InvolvedText            mediumtext charset utf8   null,
    EventsIntroText         mediumtext charset utf8   null,
    CollateralIntroText     mediumtext charset utf8   null,
    SoftwareIntroText       mediumtext charset utf8   null,
    GraphicsIntroText       mediumtext charset utf8   null,
    PromoteProductIntroText mediumtext charset utf8   null,
    constraint RecordID_Version
        unique (RecordID, Version)
)
    charset = latin1;

create index RecordID
    on MarketingPage_versions (RecordID);

create index Version
    on MarketingPage_versions (Version);

create table MarketingSoftware
(
    ID             int auto_increment
        primary key,
    ClassName      enum ('MarketingSoftware') charset utf8 default 'MarketingSoftware' null,
    LastEdited     datetime                                                            null,
    Created        datetime                                                            null,
    Name           varchar(255) charset utf8                                           null,
    Description    mediumtext charset utf8                                             null,
    YoutubeID      varchar(255) charset utf8                                           null,
    ReleaseLink    varchar(255) charset utf8                                           null,
    SortOrder      int                                     default 0                   not null,
    ParentPageID   int                                                                 null,
    LogoID         int                                                                 null,
    PresentationID int                                                                 null
)
    charset = latin1;

create index ClassName
    on MarketingSoftware (ClassName);

create index LogoID
    on MarketingSoftware (LogoID);

create index ParentPageID
    on MarketingSoftware (ParentPageID);

create index PresentationID
    on MarketingSoftware (PresentationID);

create index SortOrder
    on MarketingSoftware (SortOrder);

create table MarketingVideo
(
    ID           int auto_increment
        primary key,
    Active       tinyint unsigned default '0' not null,
    VideosID     int                          null,
    ParentPageID int                          null
)
    charset = latin1;

create index ParentPageID
    on MarketingVideo (ParentPageID);

create index VideosID
    on MarketingVideo (VideosID);

create table MarketplaceContractTemplate
(
    ID                int auto_increment
        primary key,
    MarketPlaceTypeID int null
)
    charset = latin1;

create index MarketPlaceTypeID
    on MarketplaceContractTemplate (MarketPlaceTypeID);

create table Mascot
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('Mascot') charset utf8 default 'Mascot' null,
    LastEdited datetime                                      null,
    Created    datetime                                      null,
    Name       varchar(255) charset utf8                     null,
    CodeName   varchar(255) charset utf8                     null,
    Hide       tinyint unsigned             default '0'      not null
)
    charset = latin1;

create index ClassName
    on Mascot (ClassName);

create table Member
(
    ID                        int auto_increment
        primary key,
    ClassName                 enum ('Member') charset utf8                                                                                default 'Member'      null,
    LastEdited                datetime                                                                                                                          null,
    Created                   datetime                                                                                                                          null,
    FirstName                 varchar(50) charset utf8                                                                                                          null,
    Surname                   varchar(50) charset utf8                                                                                                          null,
    Email                     varchar(254) charset utf8                                                                                                         null,
    TempIDHash                varchar(160) charset utf8                                                                                                         null,
    TempIDExpired             datetime                                                                                                                          null,
    Password                  varchar(160) charset utf8                                                                                                         null,
    RememberLoginToken        varchar(160) charset utf8                                                                                                         null,
    NumVisit                  int                                                                                                         default 0             not null,
    LastVisited               datetime                                                                                                                          null,
    AutoLoginHash             varchar(160) charset utf8                                                                                                         null,
    AutoLoginExpired          datetime                                                                                                                          null,
    PasswordEncryption        varchar(50) charset utf8                                                                                                          null,
    Salt                      varchar(50) charset utf8                                                                                                          null,
    PasswordExpiry            date                                                                                                                              null,
    LockedOutUntil            datetime                                                                                                                          null,
    Locale                    varchar(6) charset utf8                                                                                                           null,
    FailedLoginCount          int                                                                                                         default 0             not null,
    DateFormat                varchar(30) charset utf8                                                                                                          null,
    TimeFormat                varchar(30) charset utf8                                                                                                          null,
    IdentityURL               varchar(255) charset utf8                                                                                                         null,
    PresentationList          mediumtext charset utf8                                                                                                           null,
    AuthenticationToken       varchar(128) charset utf8                                                                                                         null,
    AuthenticationTokenExpire int                                                                                                         default 0             not null,
    SecondEmail               varchar(254) charset utf8                                                                                                         null,
    ThirdEmail                varchar(254) charset utf8                                                                                                         null,
    HasBeenEmailed            tinyint unsigned                                                                                            default '0'           not null,
    ShirtSize                 enum ('Extra Small', 'Small', 'Medium', 'Large', 'XL', 'XXL', 'WS', 'WM', 'WL', 'WXL', 'WXXL') charset utf8 default 'Extra Small' null,
    StatementOfInterest       mediumtext charset utf8                                                                                                           null,
    Bio                       mediumtext charset utf8                                                                                                           null,
    FoodPreference            mediumtext charset utf8                                                                                                           null,
    OtherFood                 mediumtext charset utf8                                                                                                           null,
    GitHubUser                mediumtext charset utf8                                                                                                           null,
    IRCHandle                 mediumtext charset utf8                                                                                                           null,
    TwitterName               mediumtext charset utf8                                                                                                           null,
    ContactEmail              mediumtext charset utf8                                                                                                           null,
    WeChatUser                mediumtext charset utf8                                                                                                           null,
    Projects                  mediumtext charset utf8                                                                                                           null,
    OtherProject              mediumtext charset utf8                                                                                                           null,
    SubscribedToNewsletter    tinyint unsigned                                                                                            default '0'           not null,
    JobTitle                  mediumtext charset utf8                                                                                                           null,
    DisplayOnSite             tinyint unsigned                                                                                            default '0'           not null,
    Role                      mediumtext charset utf8                                                                                                           null,
    LinkedInProfile           mediumtext charset utf8                                                                                                           null,
    Address                   varchar(255) charset utf8                                                                                                         null,
    Suburb                    varchar(64) charset utf8                                                                                                          null,
    State                     varchar(64) charset utf8                                                                                                          null,
    Postcode                  varchar(64) charset utf8                                                                                                          null,
    Country                   varchar(2) charset utf8                                                                                                           null,
    City                      varchar(64) charset utf8                                                                                                          null,
    Gender                    varchar(32) charset utf8                                                                                                          null,
    TypeOfDirector            mediumtext charset utf8                                                                                                           null,
    Active                    tinyint unsigned                                                                                            default '0'           not null,
    EmailVerified             tinyint unsigned                                                                                            default '0'           not null,
    EmailVerifiedTokenHash    mediumtext charset utf8                                                                                                           null,
    EmailVerifiedDate         datetime                                                                                                                          null,
    LegacyMember              tinyint unsigned                                                                                            default '0'           not null,
    ProfileLastUpdate         datetime                                                                                                                          null,
    Type                      enum ('None', 'Ham', 'Spam') charset utf8                                                                   default 'None'        null,
    ShowDupesOnProfile        tinyint unsigned                                                                                            default '0'           not null,
    ResignDate                datetime                                                                                                                          null,
    AskOpenStackUsername      varchar(50) charset utf8                                                                                                          null,
    VotingListID              int                                                                                                                               null,
    PhotoID                   int                                                                                                                               null,
    OrgID                     int                                                                                                                               null,
    ExternalUserId            int                                                                                                                               null,
    ExternalUserIdentifier    longtext                                                                                                                          null,
    MembershipType            enum ('Foundation', 'Community', 'None') charset utf8                                                       default 'None'        null,
    ExternalPic               varchar(512)                                                                                                                      null,
    constraint ExternalUserId
        unique (ExternalUserId)
)
    charset = latin1;

create table Candidate
(
    ID                      int auto_increment
        primary key,
    ClassName               enum ('Candidate') charset utf8 default 'Candidate' null,
    LastEdited              datetime                                            null,
    Created                 datetime                                            null,
    HasAcceptedNomination   tinyint unsigned                default '0'         not null,
    IsGoldMemberCandidate   tinyint unsigned                default '0'         not null,
    RelationshipToOpenStack mediumtext charset utf8                             null,
    Experience              mediumtext charset utf8                             null,
    BoardsRole              mediumtext charset utf8                             null,
    TopPriority             mediumtext charset utf8                             null,
    ElectionID              int                                                 null,
    MemberID                int                                                 null,
    Bio                     longtext                                            null,
    constraint FK_Candidate_Election
        foreign key (ElectionID) references Election (ID)
            on delete cascade,
    constraint FK_Candidate_Member
        foreign key (MemberID) references Member (ID)
            on delete cascade
)
    charset = latin1;

create index ClassName
    on Candidate (ClassName);

create index ElectionID
    on Candidate (ElectionID);

create index MemberID
    on Candidate (MemberID);

create index AuthenticationToken
    on Member (AuthenticationToken);

create index ClassName
    on Member (ClassName);

create index Email
    on Member (Email);

create index FirstName
    on Member (FirstName);

create index FirstName_Surname
    on Member (FirstName, Surname);

create index OrgID
    on Member (OrgID);

create index PhotoID
    on Member (PhotoID);

create index SecondEmail
    on Member (SecondEmail);

create index Surname
    on Member (Surname);

create index ThirdEmail
    on Member (ThirdEmail);

create index VotingListID
    on Member (VotingListID);

create table MemberCalendarScheduleSummitActionSyncWorkRequest
(
    ID                  int auto_increment
        primary key,
    CalendarId          varchar(255) charset utf8 null,
    CalendarName        varchar(255) charset utf8 null,
    CalendarDescription varchar(255) charset utf8 null
)
    charset = latin1;

create table MemberDeleted
(
    ID             int auto_increment
        primary key,
    ClassName      enum ('MemberDeleted') charset utf8                   default 'MemberDeleted' null,
    LastEdited     datetime                                                                      null,
    Created        datetime                                                                      null,
    FirstName      varchar(50) charset utf8                                                      null,
    Surname        varchar(50) charset utf8                                                      null,
    Email          varchar(254) charset utf8                                                     null,
    OriginalID     int                                                   default 0               not null,
    FromUrl        mediumtext charset utf8                                                       null,
    MembershipType enum ('Foundation', 'Community', 'None') charset utf8 default 'None'          null
)
    charset = latin1;

create index ClassName
    on MemberDeleted (ClassName);

create table MemberEmailChange
(
    ID            int auto_increment
        primary key,
    ClassName     enum ('MemberEmailChange') charset utf8 default 'MemberEmailChange' null,
    LastEdited    datetime                                                            null,
    Created       datetime                                                            null,
    OldValue      varchar(254) charset utf8                                           null,
    NewValue      varchar(254) charset utf8                                           null,
    MemberID      int                                                                 null,
    PerformedByID int                                                                 null
)
    charset = latin1;

create index ClassName
    on MemberEmailChange (ClassName);

create index MemberID
    on MemberEmailChange (MemberID);

create index PerformedByID
    on MemberEmailChange (PerformedByID);

create table MemberEstimatorFeed
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('MemberEstimatorFeed') charset utf8 default 'MemberEstimatorFeed' null,
    LastEdited datetime                                                                null,
    Created    datetime                                                                null,
    FirstName  varchar(50) charset utf8                                                null,
    Surname    varchar(50) charset utf8                                                null,
    Email      varchar(254) charset utf8                                               null,
    Bio        mediumtext charset utf8                                                 null,
    Type       enum ('None', 'Ham', 'Spam') charset utf8 default 'None'                null
)
    charset = latin1;

create index ClassName
    on MemberEstimatorFeed (ClassName);

create table MemberEventScheduleSummitActionSyncWorkRequest
(
    ID            int auto_increment
        primary key,
    SummitEventID int null
)
    charset = latin1;

create index SummitEventID
    on MemberEventScheduleSummitActionSyncWorkRequest (SummitEventID);

create table MemberPassword
(
    ID                 int auto_increment
        primary key,
    ClassName          enum ('MemberPassword') charset utf8 default 'MemberPassword' null,
    LastEdited         datetime                                                      null,
    Created            datetime                                                      null,
    Password           varchar(160) charset utf8                                     null,
    Salt               varchar(50) charset utf8                                      null,
    PasswordEncryption varchar(50) charset utf8                                      null,
    MemberID           int                                                           null
)
    charset = latin1;

create index ClassName
    on MemberPassword (ClassName);

create index MemberID
    on MemberPassword (MemberID);

create table MemberPasswordChange
(
    ID            int auto_increment
        primary key,
    ClassName     enum ('MemberPasswordChange') charset utf8 default 'MemberPasswordChange' null,
    LastEdited    datetime                                                                  null,
    Created       datetime                                                                  null,
    OldValue      varchar(160) charset utf8                                                 null,
    NewValue      varchar(160) charset utf8                                                 null,
    MemberID      int                                                                       null,
    PerformedByID int                                                                       null
)
    charset = latin1;

create index ClassName
    on MemberPasswordChange (ClassName);

create index MemberID
    on MemberPasswordChange (MemberID);

create index PerformedByID
    on MemberPasswordChange (PerformedByID);

create table MemberPromoCodeEmailCreationRequest
(
    ID          int auto_increment
        primary key,
    Name        varchar(254) charset utf8 null,
    Email       varchar(254) charset utf8 null,
    PromoCodeID int                       null
)
    charset = latin1;

create index PromoCodeID
    on MemberPromoCodeEmailCreationRequest (PromoCodeID);

create table MemberScheduleSummitActionSyncWorkRequest
(
    ID                 int auto_increment
        primary key,
    OwnerID            int null,
    CalendarSyncInfoID int null
)
    charset = latin1;

create index CalendarSyncInfoID
    on MemberScheduleSummitActionSyncWorkRequest (CalendarSyncInfoID);

create index OwnerID
    on MemberScheduleSummitActionSyncWorkRequest (OwnerID);

create table Member_FavoriteSummitEvents
(
    ID            int auto_increment
        primary key,
    MemberID      int default 0 not null,
    SummitEventID int default 0 not null
)
    charset = latin1;

create index MemberID
    on Member_FavoriteSummitEvents (MemberID);

create index SummitEventID
    on Member_FavoriteSummitEvents (SummitEventID);

create table Member_Schedule
(
    ID            int auto_increment
        primary key,
    MemberID      int default 0 not null,
    SummitEventID int default 0 not null
)
    charset = latin1;

create index MemberID
    on Member_Schedule (MemberID);

create index SummitEventID
    on Member_Schedule (SummitEventID);

create table Migration
(
    ID          int auto_increment
        primary key,
    ClassName   enum ('Migration') charset utf8 default 'Migration' null,
    LastEdited  datetime                                            null,
    Created     datetime                                            null,
    Name        mediumtext charset utf8                             null,
    Description mediumtext charset utf8                             null
)
    charset = latin1;

create index ClassName
    on Migration (ClassName);

create table NewDataModelSurveyMigrationMapping
(
    ID             int auto_increment
        primary key,
    OriginFieldID  int null,
    OriginSurveyID int null
)
    charset = latin1;

create index OriginFieldID
    on NewDataModelSurveyMigrationMapping (OriginFieldID);

create index OriginSurveyID
    on NewDataModelSurveyMigrationMapping (OriginSurveyID);

create table NewSchedulePage
(
    ID                  int auto_increment
        primary key,
    EnableMobileSupport tinyint unsigned default '0' not null
)
    charset = latin1;

create table NewSchedulePage_Live
(
    ID                  int auto_increment
        primary key,
    EnableMobileSupport tinyint unsigned default '0' not null
)
    charset = latin1;

create table NewSchedulePage_versions
(
    ID                  int auto_increment
        primary key,
    RecordID            int              default 0   not null,
    Version             int              default 0   not null,
    EnableMobileSupport tinyint unsigned default '0' not null,
    constraint RecordID_Version
        unique (RecordID, Version)
)
    charset = latin1;

create index RecordID
    on NewSchedulePage_versions (RecordID);

create index Version
    on NewSchedulePage_versions (Version);

create table News
(
    ID              int auto_increment
        primary key,
    ClassName       enum ('News') charset utf8 default 'News' null,
    LastEdited      datetime                                  null,
    Created         datetime                                  null,
    Date            datetime                                  null,
    Headline        mediumtext charset utf8                   null,
    Summary         mediumtext charset utf8                   null,
    SummaryHtmlFree mediumtext charset utf8                   null,
    City            mediumtext charset utf8                   null,
    State           mediumtext charset utf8                   null,
    Country         mediumtext charset utf8                   null,
    Body            mediumtext charset utf8                   null,
    BodyHtmlFree    mediumtext charset utf8                   null,
    Link            mediumtext charset utf8                   null,
    DateEmbargo     datetime                                  null,
    DateExpire      datetime                                  null,
    `Rank`          int                        default 0      not null,
    Featured        tinyint unsigned           default '0'    not null,
    Slider          tinyint unsigned           default '0'    not null,
    Approved        tinyint unsigned           default '0'    not null,
    PreApproved     tinyint unsigned           default '0'    not null,
    ShowDeclaimer   tinyint unsigned           default '0'    not null,
    IsLandscape     tinyint unsigned           default '0'    not null,
    Archived        tinyint unsigned           default '0'    not null,
    Restored        tinyint unsigned           default '0'    not null,
    Deleted         tinyint unsigned           default '0'    not null,
    EmailSent       tinyint unsigned           default '0'    not null,
    Priority        varchar(5) charset utf8                   null,
    SubmitterID     int                                       null,
    DocumentID      int                                       null,
    ImageID         int                                       null
)
    charset = latin1;

create index ClassName
    on News (ClassName);

create index DocumentID
    on News (DocumentID);

create index ImageID
    on News (ImageID);

create index SubmitterID
    on News (SubmitterID);

create table NewsTag
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('NewsTag') charset utf8 default 'NewsTag' null,
    LastEdited datetime                                        null,
    Created    datetime                                        null,
    Tag        varchar(50) charset utf8                        null
)
    charset = latin1;

create index ClassName
    on NewsTag (ClassName);

create table News_Tags
(
    ID        int auto_increment
        primary key,
    NewsID    int default 0 not null,
    NewsTagID int default 0 not null
)
    charset = latin1;

create index NewsID
    on News_Tags (NewsID);

create index NewsTagID
    on News_Tags (NewsTagID);

create table NotMyAccountAction
(
    ID               int auto_increment
        primary key,
    ClassName        enum ('NotMyAccountAction') charset utf8 default 'NotMyAccountAction' null,
    LastEdited       datetime                                                              null,
    Created          datetime                                                              null,
    PrimaryAccountID int                                                                   null,
    ForeignAccountID int                                                                   null
)
    charset = latin1;

create index ClassName
    on NotMyAccountAction (ClassName);

create index ForeignAccountID
    on NotMyAccountAction (ForeignAccountID);

create index PrimaryAccountID
    on NotMyAccountAction (PrimaryAccountID);

create table OSLogoProgramResponse
(
    ID             int auto_increment
        primary key,
    ClassName      enum ('OSLogoProgramResponse') charset utf8 default 'OSLogoProgramResponse' null,
    LastEdited     datetime                                                                    null,
    Created        datetime                                                                    null,
    FirstName      mediumtext charset utf8                                                     null,
    Surname        mediumtext charset utf8                                                     null,
    Email          mediumtext charset utf8                                                     null,
    Phone          mediumtext charset utf8                                                     null,
    Program        mediumtext charset utf8                                                     null,
    CurrentSponsor tinyint unsigned                            default '0'                     not null,
    CompanyDetails mediumtext charset utf8                                                     null,
    Product        mediumtext charset utf8                                                     null,
    Category       mediumtext charset utf8                                                     null,
    Regions        mediumtext charset utf8                                                     null,
    APIExposed     tinyint unsigned                            default '0'                     not null,
    OtherCompany   mediumtext charset utf8                                                     null,
    Projects       mediumtext charset utf8                                                     null,
    CompanyID      int                                                                         null
)
    charset = latin1;

create index ClassName
    on OSLogoProgramResponse (ClassName);

create index CompanyID
    on OSLogoProgramResponse (CompanyID);

create table OSUpstreamInstituteStudent
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('OSUpstreamInstituteStudent') charset utf8 default 'OSUpstreamInstituteStudent' null,
    LastEdited datetime                                                                              null,
    Created    datetime                                                                              null,
    FirstName  varchar(50) charset utf8                                                              null,
    LastName   varchar(50) charset utf8                                                              null,
    Email      varchar(50) charset utf8                                                              null,
    MemberID   int                                                                                   null
)
    charset = latin1;

create index ClassName
    on OSUpstreamInstituteStudent (ClassName);

create index MemberID
    on OSUpstreamInstituteStudent (MemberID);

create table Office
(
    ID           int auto_increment
        primary key,
    ClassName    enum ('Office') charset utf8 default 'Office' null,
    LastEdited   datetime                                      null,
    Created      datetime                                      null,
    Address      varchar(50) charset utf8                      null,
    Address2     varchar(50) charset utf8                      null,
    State        varchar(50) charset utf8                      null,
    ZipCode      varchar(50) charset utf8                      null,
    City         varchar(50) charset utf8                      null,
    Country      varchar(50) charset utf8                      null,
    Lat          decimal(9, 2)                default 0.00     not null,
    Lng          decimal(9, 2)                default 0.00     not null,
    `Order`      int                          default 0        not null,
    ConsultantID int                                           null
)
    charset = latin1;

create index ClassName
    on Office (ClassName);

create index ConsultantID
    on Office (ConsultantID);

create table OfficeDraft
(
    ID           int auto_increment
        primary key,
    ClassName    enum ('OfficeDraft') charset utf8 default 'OfficeDraft' null,
    LastEdited   datetime                                                null,
    Created      datetime                                                null,
    Address      varchar(50) charset utf8                                null,
    Address2     varchar(50) charset utf8                                null,
    State        varchar(50) charset utf8                                null,
    ZipCode      varchar(50) charset utf8                                null,
    City         varchar(50) charset utf8                                null,
    Country      varchar(50) charset utf8                                null,
    Lat          decimal(9, 2)                     default 0.00          not null,
    Lng          decimal(9, 2)                     default 0.00          not null,
    `Order`      int                               default 0             not null,
    ConsultantID int                                                     null
)
    charset = latin1;

create index ClassName
    on OfficeDraft (ClassName);

create index ConsultantID
    on OfficeDraft (ConsultantID);

create table OldDataModelSurveyMigrationMapping
(
    ID          int auto_increment
        primary key,
    OriginTable mediumtext charset utf8 null,
    OriginField mediumtext charset utf8 null
)
    charset = latin1;

create table OpenStackApiVersion
(
    ID                   int auto_increment
        primary key,
    ClassName            enum ('OpenStackApiVersion') charset utf8                                 default 'OpenStackApiVersion' null,
    LastEdited           datetime                                                                                                null,
    Created              datetime                                                                                                null,
    Version              varchar(50) charset utf8                                                                                null,
    Status               enum ('Deprecated', 'Supported', 'Current', 'Beta', 'Alpha') charset utf8 default 'Deprecated'          null,
    CreatedFromTask      tinyint unsigned                                                          default '0'                   not null,
    OpenStackComponentID int                                                                                                     null,
    constraint Version_Component
        unique (Version, OpenStackComponentID)
)
    charset = latin1;

create index ClassName
    on OpenStackApiVersion (ClassName);

create index OpenStackComponentID
    on OpenStackApiVersion (OpenStackComponentID);

create table OpenStackComponent
(
    ID                 int auto_increment
        primary key,
    ClassName          enum ('OpenStackComponent') charset utf8 default 'OpenStackComponent' null,
    LastEdited         datetime                                                              null,
    Created            datetime                                                              null,
    Name               varchar(255) charset utf8                                             null,
    CodeName           varchar(255) charset utf8                                             null,
    ProjectTeam        varchar(255) charset utf8                                             null,
    Description        mediumtext charset utf8                                               null,
    SupportsVersioning tinyint unsigned                         default '0'                  not null,
    SupportsExtensions tinyint unsigned                         default '0'                  not null,
    IsCoreService      tinyint unsigned                         default '0'                  not null,
    WikiUrl            mediumtext charset utf8                                               null,
    `Order`            int                                      default 0                    not null,
    YouTubeID          varchar(50) charset utf8                                              null,
    VideoDescription   mediumtext charset utf8                                               null,
    VideoTitle         varchar(50) charset utf8                                              null,
    ShowOnMarketplace  tinyint unsigned                         default '1'                  not null,
    Slug               varchar(255) charset utf8                                             null,
    Since              varchar(255) charset utf8                                             null,
    LatestReleasePTLID int                                                                   null,
    MascotID           int                                                                   null,
    CategoryID         int                                                                   null,
    DocsLinkID         int                                                                   null,
    DownloadLinkID     int                                                                   null,
    constraint NameCodeName
        unique (Name, CodeName),
    constraint Slug
        unique (Slug)
)
    charset = latin1;

create index CategoryID
    on OpenStackComponent (CategoryID);

create index ClassName
    on OpenStackComponent (ClassName);

create index CodeName
    on OpenStackComponent (CodeName);

create index DocsLinkID
    on OpenStackComponent (DocsLinkID);

create index DownloadLinkID
    on OpenStackComponent (DownloadLinkID);

create index LatestReleasePTLID
    on OpenStackComponent (LatestReleasePTLID);

create index MascotID
    on OpenStackComponent (MascotID);

create index Name
    on OpenStackComponent (Name);

create table OpenStackComponentCapabilityCategory
(
    ID          int auto_increment
        primary key,
    ClassName   enum ('OpenStackComponentCapabilityCategory') charset utf8 default 'OpenStackComponentCapabilityCategory' null,
    LastEdited  datetime                                                                                                  null,
    Created     datetime                                                                                                  null,
    Name        varchar(255) charset utf8                                                                                 null,
    Description mediumtext charset utf8                                                                                   null,
    Enabled     tinyint unsigned                                           default '1'                                    not null
)
    charset = latin1;

create index ClassName
    on OpenStackComponentCapabilityCategory (ClassName);

create table OpenStackComponentCapabilityTag
(
    ID          int auto_increment
        primary key,
    ClassName   enum ('OpenStackComponentCapabilityTag') charset utf8 default 'OpenStackComponentCapabilityTag' null,
    LastEdited  datetime                                                                                        null,
    Created     datetime                                                                                        null,
    Name        varchar(255) charset utf8                                                                       null,
    Description mediumtext charset utf8                                                                         null,
    Enabled     tinyint unsigned                                      default '1'                               not null,
    CategoryID  int                                                                                             null
)
    charset = latin1;

create index CategoryID
    on OpenStackComponentCapabilityTag (CategoryID);

create index ClassName
    on OpenStackComponentCapabilityTag (ClassName);

create table OpenStackComponentCategory
(
    ID               int auto_increment
        primary key,
    ClassName        enum ('OpenStackComponentCategory') charset utf8 default 'OpenStackComponentCategory' null,
    LastEdited       datetime                                                                              null,
    Created          datetime                                                                              null,
    Name             varchar(255) charset utf8                                                             null,
    Label            varchar(255) charset utf8                                                             null,
    Description      mediumtext charset utf8                                                               null,
    Slug             varchar(255) charset utf8                                                             null,
    `Order`          int                                              default 0                            not null,
    Enabled          tinyint unsigned                                 default '1'                          not null,
    ParentCategoryID int                                                                                   null
)
    charset = latin1;

create index ClassName
    on OpenStackComponentCategory (ClassName);

create index ParentCategoryID
    on OpenStackComponentCategory (ParentCategoryID);

create table OpenStackComponentLink
(
    ID      int auto_increment
        primary key,
    LinksID int null
)
    charset = latin1;

create index LinksID
    on OpenStackComponentLink (LinksID);

create table OpenStackComponentRelatedContent
(
    ID                   int auto_increment
        primary key,
    ClassName            enum ('OpenStackComponentRelatedContent') charset utf8 default 'OpenStackComponentRelatedContent' null,
    LastEdited           datetime                                                                                          null,
    Created              datetime                                                                                          null,
    Title                mediumtext charset utf8                                                                           null,
    Url                  mediumtext charset utf8                                                                           null,
    `Order`              int                                                    default 0                                  not null,
    OpenStackComponentID int                                                                                               null
)
    charset = latin1;

create index ClassName
    on OpenStackComponentRelatedContent (ClassName);

create index OpenStackComponentID
    on OpenStackComponentRelatedContent (OpenStackComponentID);

create table OpenStackComponentReleaseCaveat
(
    ID          int auto_increment
        primary key,
    ClassName   enum ('OpenStackComponentReleaseCaveat') charset utf8                                                 default 'OpenStackComponentReleaseCaveat' null,
    LastEdited  datetime                                                                                                                                        null,
    Created     datetime                                                                                                                                        null,
    Status      mediumtext charset utf8                                                                                                                         null,
    Label       mediumtext charset utf8                                                                                                                         null,
    Description mediumtext charset utf8                                                                                                                         null,
    Type        enum ('NotSet', 'InstallationGuide', 'QualityOfPackages', 'ProductionUse', 'SDKSupport') charset utf8 default 'NotSet'                          null,
    ReleaseID   int                                                                                                                                             null,
    ComponentID int                                                                                                                                             null
)
    charset = latin1;

create index ClassName
    on OpenStackComponentReleaseCaveat (ClassName);

create index ComponentID
    on OpenStackComponentReleaseCaveat (ComponentID);

create index ReleaseID
    on OpenStackComponentReleaseCaveat (ReleaseID);

create table OpenStackComponentTag
(
    ID                        int auto_increment
        primary key,
    ClassName                 enum ('OpenStackComponentTag') charset utf8 default 'OpenStackComponentTag' null,
    LastEdited                datetime                                                                    null,
    Created                   datetime                                                                    null,
    Name                      varchar(255) charset utf8                                                   null,
    Type                      enum ('maturity', 'info') charset utf8      default 'maturity'              null,
    Label                     varchar(255) charset utf8                                                   null,
    Description               mediumtext charset utf8                                                     null,
    Link                      varchar(255) charset utf8                                                   null,
    LabelTranslationKey       varchar(255) charset utf8                                                   null,
    DescriptionTranslationKey varchar(255) charset utf8                                                   null,
    Enabled                   tinyint unsigned                            default '1'                     not null
)
    charset = latin1;

create index ClassName
    on OpenStackComponentTag (ClassName);

create table OpenStackComponent_CapabilityTags
(
    ID                                int auto_increment
        primary key,
    OpenStackComponentID              int default 0 not null,
    OpenStackComponentCapabilityTagID int default 0 not null
)
    charset = latin1;

create index OpenStackComponentCapabilityTagID
    on OpenStackComponent_CapabilityTags (OpenStackComponentCapabilityTagID);

create index OpenStackComponentID
    on OpenStackComponent_CapabilityTags (OpenStackComponentID);

create table OpenStackComponent_Dependencies
(
    ID                   int auto_increment
        primary key,
    OpenStackComponentID int default 0 not null,
    ChildID              int default 0 not null
)
    charset = latin1;

create index ChildID
    on OpenStackComponent_Dependencies (ChildID);

create index OpenStackComponentID
    on OpenStackComponent_Dependencies (OpenStackComponentID);

create table OpenStackComponent_RelatedComponents
(
    ID                   int auto_increment
        primary key,
    OpenStackComponentID int default 0 not null,
    ChildID              int default 0 not null
)
    charset = latin1;

create index ChildID
    on OpenStackComponent_RelatedComponents (ChildID);

create index OpenStackComponentID
    on OpenStackComponent_RelatedComponents (OpenStackComponentID);

create table OpenStackComponent_SupportTeams
(
    ID                   int auto_increment
        primary key,
    OpenStackComponentID int default 0 not null,
    ChildID              int default 0 not null
)
    charset = latin1;

create index ChildID
    on OpenStackComponent_SupportTeams (ChildID);

create index OpenStackComponentID
    on OpenStackComponent_SupportTeams (OpenStackComponentID);

create table OpenStackComponent_Tags
(
    ID                      int auto_increment
        primary key,
    OpenStackComponentID    int default 0 not null,
    OpenStackComponentTagID int default 0 not null,
    SortOrder               int default 0 not null
)
    charset = latin1;

create index OpenStackComponentID
    on OpenStackComponent_Tags (OpenStackComponentID);

create index OpenStackComponentTagID
    on OpenStackComponent_Tags (OpenStackComponentTagID);

create table OpenStackDaysDoc
(
    ID                   int auto_increment
        primary key,
    ClassName            enum ('OpenStackDaysDoc') charset utf8 default 'OpenStackDaysDoc' null,
    LastEdited           datetime                                                          null,
    Created              datetime                                                          null,
    Label                varchar(255) charset utf8                                         null,
    `Group`              varchar(255) charset utf8                                         null,
    SortOrder            int                                    default 0                  not null,
    OfficialGuidelinesID int                                                               null,
    PlanningToolsID      int                                                               null,
    ArtworkID            int                                                               null,
    MediaID              int                                                               null,
    CollateralsID        int                                                               null,
    DocID                int                                                               null,
    ThumbnailID          int                                                               null,
    ParentPageID         int                                                               null
)
    charset = latin1;

create index ArtworkID
    on OpenStackDaysDoc (ArtworkID);

create index ClassName
    on OpenStackDaysDoc (ClassName);

create index CollateralsID
    on OpenStackDaysDoc (CollateralsID);

create index DocID
    on OpenStackDaysDoc (DocID);

create index MediaID
    on OpenStackDaysDoc (MediaID);

create index OfficialGuidelinesID
    on OpenStackDaysDoc (OfficialGuidelinesID);

create index ParentPageID
    on OpenStackDaysDoc (ParentPageID);

create index PlanningToolsID
    on OpenStackDaysDoc (PlanningToolsID);

create index ThumbnailID
    on OpenStackDaysDoc (ThumbnailID);

create table OpenStackDaysImage
(
    ID           int auto_increment
        primary key,
    SortOrder    int default 0 not null,
    HeaderPicsID int           null,
    ParentPageID int           null
)
    charset = latin1;

create index HeaderPicsID
    on OpenStackDaysImage (HeaderPicsID);

create index ParentPageID
    on OpenStackDaysImage (ParentPageID);

create table OpenStackDaysPage
(
    ID               int auto_increment
        primary key,
    AboutDescription mediumtext charset utf8 null,
    HostIntro        mediumtext charset utf8 null,
    HostFAQs         mediumtext charset utf8 null,
    ToolkitDesc      mediumtext charset utf8 null,
    ArtworkIntro     mediumtext charset utf8 null,
    CollateralIntro  mediumtext charset utf8 null
)
    charset = latin1;

create table OpenStackDaysPage_Live
(
    ID               int auto_increment
        primary key,
    AboutDescription mediumtext charset utf8 null,
    HostIntro        mediumtext charset utf8 null,
    HostFAQs         mediumtext charset utf8 null,
    ToolkitDesc      mediumtext charset utf8 null,
    ArtworkIntro     mediumtext charset utf8 null,
    CollateralIntro  mediumtext charset utf8 null
)
    charset = latin1;

create table OpenStackDaysPage_versions
(
    ID               int auto_increment
        primary key,
    RecordID         int default 0           not null,
    Version          int default 0           not null,
    AboutDescription mediumtext charset utf8 null,
    HostIntro        mediumtext charset utf8 null,
    HostFAQs         mediumtext charset utf8 null,
    ToolkitDesc      mediumtext charset utf8 null,
    ArtworkIntro     mediumtext charset utf8 null,
    CollateralIntro  mediumtext charset utf8 null,
    constraint RecordID_Version
        unique (RecordID, Version)
)
    charset = latin1;

create index RecordID
    on OpenStackDaysPage_versions (RecordID);

create index Version
    on OpenStackDaysPage_versions (Version);

create table OpenStackDaysVideo
(
    ID            int auto_increment
        primary key,
    Active        tinyint unsigned default '0' not null,
    AboutID       int                          null,
    AboutHackID   int                          null,
    CollateralsID int                          null,
    ParentPageID  int                          null
)
    charset = latin1;

create index AboutHackID
    on OpenStackDaysVideo (AboutHackID);

create index AboutID
    on OpenStackDaysVideo (AboutID);

create index CollateralsID
    on OpenStackDaysVideo (CollateralsID);

create index ParentPageID
    on OpenStackDaysVideo (ParentPageID);

create table OpenStackFoundationStaffPage
(
    ID              int auto_increment
        primary key,
    ExtraFoundation mediumtext charset utf8 null,
    ExtraSupporting mediumtext charset utf8 null,
    ExtraFooter     mediumtext charset utf8 null
)
    charset = latin1;

create table OpenStackFoundationStaffPage_Live
(
    ID              int auto_increment
        primary key,
    ExtraFoundation mediumtext charset utf8 null,
    ExtraSupporting mediumtext charset utf8 null,
    ExtraFooter     mediumtext charset utf8 null
)
    charset = latin1;

create table OpenStackFoundationStaffPage_versions
(
    ID              int auto_increment
        primary key,
    RecordID        int default 0           not null,
    Version         int default 0           not null,
    ExtraFoundation mediumtext charset utf8 null,
    ExtraSupporting mediumtext charset utf8 null,
    ExtraFooter     mediumtext charset utf8 null,
    constraint RecordID_Version
        unique (RecordID, Version)
)
    charset = latin1;

create index RecordID
    on OpenStackFoundationStaffPage_versions (RecordID);

create index Version
    on OpenStackFoundationStaffPage_versions (Version);

create table OpenStackImplementation
(
    ID                              int auto_increment
        primary key,
    CompatibleWithCompute           tinyint unsigned default '0' not null,
    CompatibleWithStorage           tinyint unsigned default '0' not null,
    CompatibleWithFederatedIdentity tinyint unsigned default '0' not null,
    UsesIronic                      tinyint unsigned default '0' not null,
    ExpiryDate                      datetime                     null,
    Notes                           mediumtext charset utf8      null,
    ProgramVersionID                int                          null,
    ReportedReleaseID               int                          null,
    PassedReleaseID                 int                          null
)
    charset = latin1;

create index PassedReleaseID
    on OpenStackImplementation (PassedReleaseID);

create index ProgramVersionID
    on OpenStackImplementation (ProgramVersionID);

create index ReportedReleaseID
    on OpenStackImplementation (ReportedReleaseID);

create table OpenStackImplementationApiCoverage
(
    ID                           int auto_increment
        primary key,
    ClassName                    enum ('OpenStackImplementationApiCoverage', 'CloudServiceOffered') charset utf8 default 'OpenStackImplementationApiCoverage' null,
    LastEdited                   datetime                                                                                                                     null,
    Created                      datetime                                                                                                                     null,
    CoveragePercent              int                                                                             default 0                                    not null,
    ImplementationID             int                                                                                                                          null,
    ReleaseSupportedApiVersionID int                                                                                                                          null
)
    charset = latin1;

create index ClassName
    on OpenStackImplementationApiCoverage (ClassName);

create index ImplementationID
    on OpenStackImplementationApiCoverage (ImplementationID);

create index ReleaseSupportedApiVersionID
    on OpenStackImplementationApiCoverage (ReleaseSupportedApiVersionID);

create table OpenStackImplementationApiCoverageDraft
(
    ID                           int auto_increment
        primary key,
    ClassName                    enum ('OpenStackImplementationApiCoverageDraft', 'CloudServiceOfferedDraft') charset utf8 default 'OpenStackImplementationApiCoverageDraft' null,
    LastEdited                   datetime                                                                                                                                    null,
    Created                      datetime                                                                                                                                    null,
    CoveragePercent              int                                                                                       default 0                                         not null,
    ImplementationID             int                                                                                                                                         null,
    ReleaseSupportedApiVersionID int                                                                                                                                         null
)
    charset = latin1;

create index ClassName
    on OpenStackImplementationApiCoverageDraft (ClassName);

create index ImplementationID
    on OpenStackImplementationApiCoverageDraft (ImplementationID);

create index ReleaseSupportedApiVersionID
    on OpenStackImplementationApiCoverageDraft (ReleaseSupportedApiVersionID);

create table OpenStackImplementationDraft
(
    ID                              int auto_increment
        primary key,
    CompatibleWithCompute           tinyint unsigned default '0' not null,
    CompatibleWithStorage           tinyint unsigned default '0' not null,
    CompatibleWithPlatform          tinyint unsigned default '0' not null,
    ExpiryDate                      datetime                     null,
    CompatibleWithFederatedIdentity tinyint unsigned default '0' not null,
    ProgramVersionID                int                          null
)
    charset = latin1;

create index ProgramVersionID
    on OpenStackImplementationDraft (ProgramVersionID);

create table OpenStackImplementationDraft_Guests
(
    ID                             int auto_increment
        primary key,
    OpenStackImplementationDraftID int default 0 not null,
    GuestOSTypeID                  int default 0 not null
)
    charset = latin1;

create index GuestOSTypeID
    on OpenStackImplementationDraft_Guests (GuestOSTypeID);

create index OpenStackImplementationDraftID
    on OpenStackImplementationDraft_Guests (OpenStackImplementationDraftID);

create table OpenStackImplementationDraft_HyperVisors
(
    ID                             int auto_increment
        primary key,
    OpenStackImplementationDraftID int default 0 not null,
    HyperVisorTypeID               int default 0 not null
)
    charset = latin1;

create index HyperVisorTypeID
    on OpenStackImplementationDraft_HyperVisors (HyperVisorTypeID);

create index OpenStackImplementationDraftID
    on OpenStackImplementationDraft_HyperVisors (OpenStackImplementationDraftID);

create table OpenStackImplementation_Guests
(
    ID                        int auto_increment
        primary key,
    OpenStackImplementationID int default 0 not null,
    GuestOSTypeID             int default 0 not null
)
    charset = latin1;

create index GuestOSTypeID
    on OpenStackImplementation_Guests (GuestOSTypeID);

create index OpenStackImplementationID
    on OpenStackImplementation_Guests (OpenStackImplementationID);

create table OpenStackImplementation_HyperVisors
(
    ID                        int auto_increment
        primary key,
    OpenStackImplementationID int default 0 not null,
    HyperVisorTypeID          int default 0 not null
)
    charset = latin1;

create index HyperVisorTypeID
    on OpenStackImplementation_HyperVisors (HyperVisorTypeID);

create index OpenStackImplementationID
    on OpenStackImplementation_HyperVisors (OpenStackImplementationID);

create table OpenStackPoweredProgramHistory
(
    ID                           int auto_increment
        primary key,
    ClassName                    enum ('OpenStackPoweredProgramHistory') charset utf8 default 'OpenStackPoweredProgramHistory' null,
    LastEdited                   datetime                                                                                      null,
    Created                      datetime                                                                                      null,
    CompatibleWithComputeBefore  tinyint unsigned                                     default '0'                              not null,
    CompatibleWithStorageBefore  tinyint unsigned                                     default '0'                              not null,
    ExpiryDateBefore             datetime                                                                                      null,
    ProgramVersionIDBefore       int                                                  default 0                                not null,
    ProgramVersionNameBefore     varchar(50) charset utf8                                                                      null,
    CompatibleWithComputeCurrent tinyint unsigned                                     default '0'                              not null,
    CompatibleWithStorageCurrent tinyint unsigned                                     default '0'                              not null,
    ExpiryDateCurrent            datetime                                                                                      null,
    ProgramVersionIDCurrent      int                                                  default 0                                not null,
    ProgramVersionNameCurrent    varchar(50) charset utf8                                                                      null,
    ReportedReleaseIDBefore      int                                                  default 0                                not null,
    ReportedReleaseIDCurrent     int                                                  default 0                                not null,
    ReportedReleaseNameBefore    varchar(50) charset utf8                                                                      null,
    ReportedReleaseNameCurrent   varchar(50) charset utf8                                                                      null,
    PassedReleaseIDBefore        int                                                  default 0                                not null,
    PassedReleaseIDCurrent       int                                                  default 0                                not null,
    PassedReleaseNameBefore      varchar(50) charset utf8                                                                      null,
    PassedReleaseNameCurrent     varchar(50) charset utf8                                                                      null,
    NotesBefore                  mediumtext charset utf8                                                                       null,
    NotesCurrent                 mediumtext charset utf8                                                                       null,
    OpenStackImplementationID    int                                                                                           null,
    OwnerID                      int                                                                                           null
)
    charset = latin1;

create index ClassName
    on OpenStackPoweredProgramHistory (ClassName);

create index OpenStackImplementationID
    on OpenStackPoweredProgramHistory (OpenStackImplementationID);

create index OwnerID
    on OpenStackPoweredProgramHistory (OwnerID);

create table OpenStackRelease
(
    ID              int auto_increment
        primary key,
    ClassName       enum ('OpenStackRelease') charset utf8                                                                default 'OpenStackRelease' null,
    LastEdited      datetime                                                                                                                         null,
    Created         datetime                                                                                                                         null,
    Name            varchar(50) charset utf8                                                                                                         null,
    ReleaseNumber   varchar(50) charset utf8                                                                                                         null,
    ReleaseDate     date                                                                                                                             null,
    ReleaseNotesUrl mediumtext charset utf8                                                                                                          null,
    Status          enum ('Deprecated', 'EOL', 'SecuritySupported', 'Current', 'UnderDevelopment', 'Future') charset utf8 default 'Deprecated'       null,
    HasStatistics   tinyint unsigned                                                                                      default '0'                not null,
    constraint Name
        unique (Name),
    constraint ReleaseDate
        unique (ReleaseDate),
    constraint ReleaseNumber
        unique (ReleaseNumber)
)
    charset = latin1;

create index ClassName
    on OpenStackRelease (ClassName);

create table OpenStackReleaseSupportedApiVersion
(
    ID                   int auto_increment
        primary key,
    ClassName            enum ('OpenStackReleaseSupportedApiVersion') charset utf8                 default 'OpenStackReleaseSupportedApiVersion' null,
    LastEdited           datetime                                                                                                                null,
    Created              datetime                                                                                                                null,
    ReleaseVersion       mediumtext charset utf8                                                                                                 null,
    Status               enum ('Deprecated', 'Supported', 'Current', 'Beta', 'Alpha') charset utf8 default 'Current'                             null,
    CreatedFromTask      tinyint unsigned                                                          default '0'                                   not null,
    OpenStackComponentID int                                                                                                                     null,
    ApiVersionID         int                                                                                                                     null,
    ReleaseID            int                                                                                                                     null,
    constraint Component_ApiVersion_Release
        unique (OpenStackComponentID, ApiVersionID, ReleaseID)
)
    charset = latin1;

create index ApiVersionID
    on OpenStackReleaseSupportedApiVersion (ApiVersionID);

create index ClassName
    on OpenStackReleaseSupportedApiVersion (ClassName);

create index OpenStackComponentID
    on OpenStackReleaseSupportedApiVersion (OpenStackComponentID);

create index ReleaseID
    on OpenStackReleaseSupportedApiVersion (ReleaseID);

create table OpenStackRelease_OpenStackComponents
(
    ID                                     int auto_increment
        primary key,
    OpenStackReleaseID                     int              default 0   not null,
    OpenStackComponentID                   int              default 0   not null,
    Adoption                               int              default 0   not null,
    MaturityPoints                         int              default 0   not null,
    HasInstallationGuide                   tinyint unsigned default '0' not null,
    SDKSupport                             int              default 0   not null,
    QualityOfPackages                      mediumtext charset utf8      null,
    MostActiveContributorsByCompanyJson    mediumtext charset utf8      null,
    MostActiveContributorsByIndividualJson mediumtext charset utf8      null,
    ContributionsJson                      mediumtext charset utf8      null,
    ReleaseMileStones                      tinyint unsigned default '0' not null,
    ReleaseCycleWithIntermediary           tinyint unsigned default '0' not null,
    ReleaseIndependent                     tinyint unsigned default '0' not null,
    ReleaseTrailing                        tinyint unsigned default '0' not null,
    ReleasesNotes                          mediumtext charset utf8      null,
    CustomTeamYAMLFileName                 mediumtext charset utf8      null
)
    charset = latin1;

create index OpenStackComponentID
    on OpenStackRelease_OpenStackComponents (OpenStackComponentID);

create index OpenStackReleaseID
    on OpenStackRelease_OpenStackComponents (OpenStackReleaseID);

create table OpenStackSampleConfig
(
    ID          int auto_increment
        primary key,
    ClassName   enum ('OpenStackSampleConfig') charset utf8 default 'OpenStackSampleConfig' null,
    LastEdited  datetime                                                                    null,
    Created     datetime                                                                    null,
    Title       varchar(50) charset utf8                                                    null,
    Summary     mediumtext charset utf8                                                     null,
    Description mediumtext charset utf8                                                     null,
    IsDefault   tinyint unsigned                            default '0'                     not null,
    `Order`     int                                         default 0                       not null,
    CuratorID   int                                                                         null,
    ReleaseID   int                                                                         null,
    TypeID      int                                                                         null
)
    charset = latin1;

create index ClassName
    on OpenStackSampleConfig (ClassName);

create index CuratorID
    on OpenStackSampleConfig (CuratorID);

create index ReleaseID
    on OpenStackSampleConfig (ReleaseID);

create index TypeID
    on OpenStackSampleConfig (TypeID);

create table OpenStackSampleConfigRelatedNote
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('OpenStackSampleConfigRelatedNote') charset utf8 default 'OpenStackSampleConfigRelatedNote' null,
    LastEdited datetime                                                                                          null,
    Created    datetime                                                                                          null,
    Title      mediumtext charset utf8                                                                           null,
    Link       mediumtext charset utf8                                                                           null,
    `Order`    int                                                    default 0                                  not null,
    ConfigID   int                                                                                               null
)
    charset = latin1;

create index ClassName
    on OpenStackSampleConfigRelatedNote (ClassName);

create index ConfigID
    on OpenStackSampleConfigRelatedNote (ConfigID);

create table OpenStackSampleConfig_OpenStackComponents
(
    ID                      int auto_increment
        primary key,
    OpenStackSampleConfigID int default 0 not null,
    OpenStackComponentID    int default 0 not null,
    `Order`                 int default 0 not null
)
    charset = latin1;

create index OpenStackComponentID
    on OpenStackSampleConfig_OpenStackComponents (OpenStackComponentID);

create index OpenStackSampleConfigID
    on OpenStackSampleConfig_OpenStackComponents (OpenStackSampleConfigID);

create table OpenStackSampleConfigurationType
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('OpenStackSampleConfigurationType') charset utf8 default 'OpenStackSampleConfigurationType' null,
    LastEdited datetime                                                                                          null,
    Created    datetime                                                                                          null,
    Type       mediumtext charset utf8                                                                           null,
    `Order`    int                                                    default 0                                  not null,
    IsDefault  tinyint unsigned                                       default '0'                                not null,
    ReleaseID  int                                                                                               null
)
    charset = latin1;

create index ClassName
    on OpenStackSampleConfigurationType (ClassName);

create index ReleaseID
    on OpenStackSampleConfigurationType (ReleaseID);

create table OpenStackUserRequest
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('OpenStackUserRequest') charset utf8 default 'OpenStackUserRequest' null,
    LastEdited datetime                                                                  null,
    Created    datetime                                                                  null,
    Name       mediumtext charset utf8                                                   null,
    Company    mediumtext charset utf8                                                   null,
    Email      mediumtext charset utf8                                                   null
)
    charset = latin1;

create index ClassName
    on OpenStackUserRequest (ClassName);

create table OpenstackUser
(
    ID              int auto_increment
        primary key,
    ListedOnSite    tinyint unsigned                                                                         default '0'          not null,
    FeaturedOnSite  tinyint unsigned                                                                         default '0'          not null,
    Objectives      mediumtext charset utf8                                                                                       null,
    PullQuote       mediumtext charset utf8                                                                                       null,
    PullQuoteAuthor varchar(255) charset utf8                                                                                     null,
    URL             varchar(255) charset utf8                                                                                     null,
    Industry        varchar(255) charset utf8                                                                                     null,
    Headquarters    mediumtext charset utf8                                                                                       null,
    Size            varchar(255) charset utf8                                                                                     null,
    Category        enum ('StartupSMB', 'Enterprise', 'ServiceProvider', 'AcademicGovResearch') charset utf8 default 'StartupSMB' null,
    UseCase         enum ('Unknown', 'Saas', 'TestDev', 'BigDataAnalytics') charset utf8                     default 'Unknown'    null,
    LogoID          int                                                                                                           null
)
    charset = latin1;

create index LogoID
    on OpenstackUser (LogoID);

create table OpenstackUser_Live
(
    ID              int auto_increment
        primary key,
    ListedOnSite    tinyint unsigned                                                                         default '0'          not null,
    FeaturedOnSite  tinyint unsigned                                                                         default '0'          not null,
    Objectives      mediumtext charset utf8                                                                                       null,
    PullQuote       mediumtext charset utf8                                                                                       null,
    PullQuoteAuthor varchar(255) charset utf8                                                                                     null,
    URL             varchar(255) charset utf8                                                                                     null,
    Industry        varchar(255) charset utf8                                                                                     null,
    Headquarters    mediumtext charset utf8                                                                                       null,
    Size            varchar(255) charset utf8                                                                                     null,
    Category        enum ('StartupSMB', 'Enterprise', 'ServiceProvider', 'AcademicGovResearch') charset utf8 default 'StartupSMB' null,
    UseCase         enum ('Unknown', 'Saas', 'TestDev', 'BigDataAnalytics') charset utf8                     default 'Unknown'    null,
    LogoID          int                                                                                                           null
)
    charset = latin1;

create index LogoID
    on OpenstackUser_Live (LogoID);

create table OpenstackUser_Projects
(
    ID              int auto_increment
        primary key,
    OpenstackUserID int default 0 not null,
    ProjectID       int default 0 not null
)
    charset = latin1;

create index OpenstackUserID
    on OpenstackUser_Projects (OpenstackUserID);

create index ProjectID
    on OpenstackUser_Projects (ProjectID);

create table OpenstackUser_versions
(
    ID              int auto_increment
        primary key,
    RecordID        int                                                                                      default 0            not null,
    Version         int                                                                                      default 0            not null,
    ListedOnSite    tinyint unsigned                                                                         default '0'          not null,
    FeaturedOnSite  tinyint unsigned                                                                         default '0'          not null,
    Objectives      mediumtext charset utf8                                                                                       null,
    PullQuote       mediumtext charset utf8                                                                                       null,
    PullQuoteAuthor varchar(255) charset utf8                                                                                     null,
    URL             varchar(255) charset utf8                                                                                     null,
    Industry        varchar(255) charset utf8                                                                                     null,
    Headquarters    mediumtext charset utf8                                                                                       null,
    Size            varchar(255) charset utf8                                                                                     null,
    Category        enum ('StartupSMB', 'Enterprise', 'ServiceProvider', 'AcademicGovResearch') charset utf8 default 'StartupSMB' null,
    UseCase         enum ('Unknown', 'Saas', 'TestDev', 'BigDataAnalytics') charset utf8                     default 'Unknown'    null,
    LogoID          int                                                                                                           null,
    constraint RecordID_Version
        unique (RecordID, Version)
)
    charset = latin1;

create index LogoID
    on OpenstackUser_versions (LogoID);

create index RecordID
    on OpenstackUser_versions (RecordID);

create index Version
    on OpenstackUser_versions (Version);

create table Org
(
    ID                     int auto_increment
        primary key,
    ClassName              enum ('Org') charset utf8                                                                                               default 'Org'             null,
    LastEdited             datetime                                                                                                                                          null,
    Created                datetime                                                                                                                                          null,
    Name                   mediumtext charset utf8                                                                                                                           null,
    IsStandardizedOrg      tinyint unsigned                                                                                                        default '0'               not null,
    FoundationSupportLevel enum ('Platinum Member', 'Gold Member', 'Corporate Sponsor', 'Startup Sponsor', 'Supporting Organization') charset utf8 default 'Platinum Member' null,
    OrgProfileID           int                                                                                                                                               null
)
    charset = latin1;

create index ClassName
    on Org (ClassName);

create index OrgProfileID
    on Org (OrgProfileID);

create fulltext index SearchFields
    on Org (Name);

create table Org_InvolvementTypes
(
    ID                int auto_increment
        primary key,
    OrgID             int default 0 not null,
    InvolvementTypeID int default 0 not null
)
    charset = latin1;

create index InvolvementTypeID
    on Org_InvolvementTypes (InvolvementTypeID);

create index OrgID
    on Org_InvolvementTypes (OrgID);

create table OrganizationRegistrationRequest
(
    ID             int auto_increment
        primary key,
    ClassName      enum ('OrganizationRegistrationRequest') charset utf8 default 'OrganizationRegistrationRequest' null,
    LastEdited     datetime                                                                                        null,
    Created        datetime                                                                                        null,
    MemberID       int                                                                                             null,
    OrganizationID int                                                                                             null
)
    charset = latin1;

create index ClassName
    on OrganizationRegistrationRequest (ClassName);

create index MemberID
    on OrganizationRegistrationRequest (MemberID);

create index OrganizationID
    on OrganizationRegistrationRequest (OrganizationID);

create table PTGDynamic
(
    ID                 int auto_increment
        primary key,
    Summary            mediumtext charset utf8   null,
    WhyTheChange       mediumtext charset utf8   null,
    HotelAndTravel     mediumtext charset utf8   null,
    HotelLink          varchar(255) charset utf8 null,
    WhoShouldAttend    mediumtext charset utf8   null,
    WhoShouldNotAttend mediumtext charset utf8   null,
    Benefits           mediumtext charset utf8   null,
    SponsorLogos       mediumtext charset utf8   null,
    Sponsor            mediumtext charset utf8   null,
    SponsorSteps       mediumtext charset utf8   null,
    TravelSupport      mediumtext charset utf8   null,
    TravelSupportApply mediumtext charset utf8   null,
    RegisterToAttend   mediumtext charset utf8   null,
    PTGSchedule        mediumtext charset utf8   null,
    CodeOfConduct      mediumtext charset utf8   null,
    FindOutMore        mediumtext charset utf8   null,
    FAQText            mediumtext charset utf8   null,
    GraphID            int                       null,
    ScheduleImageID    int                       null
)
    charset = latin1;

create index GraphID
    on PTGDynamic (GraphID);

create index ScheduleImageID
    on PTGDynamic (ScheduleImageID);

create table PTGDynamic_Live
(
    ID                 int auto_increment
        primary key,
    Summary            mediumtext charset utf8   null,
    WhyTheChange       mediumtext charset utf8   null,
    HotelAndTravel     mediumtext charset utf8   null,
    HotelLink          varchar(255) charset utf8 null,
    WhoShouldAttend    mediumtext charset utf8   null,
    WhoShouldNotAttend mediumtext charset utf8   null,
    Benefits           mediumtext charset utf8   null,
    SponsorLogos       mediumtext charset utf8   null,
    Sponsor            mediumtext charset utf8   null,
    SponsorSteps       mediumtext charset utf8   null,
    TravelSupport      mediumtext charset utf8   null,
    TravelSupportApply mediumtext charset utf8   null,
    RegisterToAttend   mediumtext charset utf8   null,
    PTGSchedule        mediumtext charset utf8   null,
    CodeOfConduct      mediumtext charset utf8   null,
    FindOutMore        mediumtext charset utf8   null,
    FAQText            mediumtext charset utf8   null,
    GraphID            int                       null,
    ScheduleImageID    int                       null
)
    charset = latin1;

create index GraphID
    on PTGDynamic_Live (GraphID);

create index ScheduleImageID
    on PTGDynamic_Live (ScheduleImageID);

create table PTGDynamic_versions
(
    ID                 int auto_increment
        primary key,
    RecordID           int default 0             not null,
    Version            int default 0             not null,
    Summary            mediumtext charset utf8   null,
    WhyTheChange       mediumtext charset utf8   null,
    HotelAndTravel     mediumtext charset utf8   null,
    HotelLink          varchar(255) charset utf8 null,
    WhoShouldAttend    mediumtext charset utf8   null,
    WhoShouldNotAttend mediumtext charset utf8   null,
    Benefits           mediumtext charset utf8   null,
    SponsorLogos       mediumtext charset utf8   null,
    Sponsor            mediumtext charset utf8   null,
    SponsorSteps       mediumtext charset utf8   null,
    TravelSupport      mediumtext charset utf8   null,
    TravelSupportApply mediumtext charset utf8   null,
    RegisterToAttend   mediumtext charset utf8   null,
    PTGSchedule        mediumtext charset utf8   null,
    CodeOfConduct      mediumtext charset utf8   null,
    FindOutMore        mediumtext charset utf8   null,
    FAQText            mediumtext charset utf8   null,
    GraphID            int                       null,
    ScheduleImageID    int                       null,
    constraint RecordID_Version
        unique (RecordID, Version)
)
    charset = latin1;

create index GraphID
    on PTGDynamic_versions (GraphID);

create index RecordID
    on PTGDynamic_versions (RecordID);

create index ScheduleImageID
    on PTGDynamic_versions (ScheduleImageID);

create index Version
    on PTGDynamic_versions (Version);

create table Page
(
    ID               int auto_increment
        primary key,
    IncludeJquery    tinyint unsigned default '0' not null,
    PageJavaScript   mediumtext charset utf8      null,
    IncludeShadowBox tinyint unsigned default '0' not null,
    MetaTitle        varchar(255) charset utf8    null,
    PublishDate      datetime                     null,
    MetaImageID      int                          null
)
    charset = latin1;

create index MetaImageID
    on Page (MetaImageID);

create table PageLink
(
    ID     int auto_increment
        primary key,
    PageID int null
)
    charset = latin1;

create index PageID
    on PageLink (PageID);

create table PageSection
(
    ID           int auto_increment
        primary key,
    ClassName    enum ('PageSection', 'PageSectionMovement', 'PageSectionText', 'PageSectionBoxes', 'PageSectionLinks', 'PageSectionPicture', 'PageSectionSpeakers', 'PageSectionSponsors', 'PageSectionVideos') charset utf8 default 'PageSection' null,
    LastEdited   datetime                                                                                                                                                                                                                           null,
    Created      datetime                                                                                                                                                                                                                           null,
    Name         varchar(100) charset utf8                                                                                                                                                                                                          null,
    Title        varchar(255) charset utf8                                                                                                                                                                                                          null,
    IconClass    varchar(50) charset utf8                                                                                                                                                                                                           null,
    WrapperClass varchar(100) charset utf8                                                                                                                                                                                                          null,
    ShowInNav    tinyint unsigned                                                                                                                                                                                             default '0'           not null,
    Enabled      tinyint unsigned                                                                                                                                                                                             default '1'           not null,
    `Order`      int                                                                                                                                                                                                          default 0             not null,
    ParentPageID int                                                                                                                                                                                                                                null
)
    charset = latin1;

create index ClassName
    on PageSection (ClassName);

create index ParentPageID
    on PageSection (ParentPageID);

create table PageSectionBox
(
    ID              int auto_increment
        primary key,
    ClassName       enum ('PageSectionBox', 'PageSectionBoxQuote', 'PageSectionBoxVideo') charset utf8 default 'PageSectionBox' null,
    LastEdited      datetime                                                                                                    null,
    Created         datetime                                                                                                    null,
    Title           varchar(255) charset utf8                                                                                   null,
    Text            mediumtext charset utf8                                                                                     null,
    ButtonLink      varchar(255) charset utf8                                                                                   null,
    ButtonText      varchar(100) charset utf8                                                                                   null,
    Size            int                                                                                default 0                not null,
    `Order`         int                                                                                default 0                not null,
    ParentSectionID int                                                                                                         null
)
    charset = latin1;

create index ClassName
    on PageSectionBox (ClassName);

create index ParentSectionID
    on PageSectionBox (ParentSectionID);

create table PageSectionBoxQuote
(
    ID        int auto_increment
        primary key,
    SpeakerID int null
)
    charset = latin1;

create index SpeakerID
    on PageSectionBoxQuote (SpeakerID);

create table PageSectionBoxVideo
(
    ID          int auto_increment
        primary key,
    YoutubeID   varchar(100) charset utf8 null,
    ThumbnailID int                       null
)
    charset = latin1;

create index ThumbnailID
    on PageSectionBoxVideo (ThumbnailID);

create table PageSectionLinks_Links
(
    ID                 int auto_increment
        primary key,
    PageSectionLinksID int default 0 not null,
    LinkID             int default 0 not null,
    `Order`            int default 0 not null
)
    charset = latin1;

create index LinkID
    on PageSectionLinks_Links (LinkID);

create index PageSectionLinksID
    on PageSectionLinks_Links (PageSectionLinksID);

create table PageSectionMovement
(
    ID         int auto_increment
        primary key,
    TextTop    mediumtext charset utf8 null,
    TextBottom mediumtext charset utf8 null,
    PictureID  int                     null
)
    charset = latin1;

create index PictureID
    on PageSectionMovement (PictureID);

create table PageSectionPicture
(
    ID        int auto_increment
        primary key,
    PictureID int null
)
    charset = latin1;

create index PictureID
    on PageSectionPicture (PictureID);

create table PageSectionSpeakers_Speakers
(
    ID                    int auto_increment
        primary key,
    PageSectionSpeakersID int default 0 not null,
    PresentationSpeakerID int default 0 not null,
    `Order`               int default 0 not null
)
    charset = latin1;

create index PageSectionSpeakersID
    on PageSectionSpeakers_Speakers (PageSectionSpeakersID);

create index PresentationSpeakerID
    on PageSectionSpeakers_Speakers (PresentationSpeakerID);

create table PageSectionSponsors_Sponsors
(
    ID                    int auto_increment
        primary key,
    PageSectionSponsorsID int default 0 not null,
    CompanyID             int default 0 not null,
    `Order`               int default 0 not null
)
    charset = latin1;

create index CompanyID
    on PageSectionSponsors_Sponsors (CompanyID);

create index PageSectionSponsorsID
    on PageSectionSponsors_Sponsors (PageSectionSponsorsID);

create table PageSectionText
(
    ID   int auto_increment
        primary key,
    Text mediumtext charset utf8 null
)
    charset = latin1;

create table PageSectionVideos_Videos
(
    ID                  int auto_increment
        primary key,
    PageSectionVideosID int default 0 not null,
    VideoLinkID         int default 0 not null,
    `Order`             int default 0 not null
)
    charset = latin1;

create index PageSectionVideosID
    on PageSectionVideos_Videos (PageSectionVideosID);

create index VideoLinkID
    on PageSectionVideos_Videos (VideoLinkID);

create table Page_Live
(
    ID               int auto_increment
        primary key,
    IncludeJquery    tinyint unsigned default '0' not null,
    PageJavaScript   mediumtext charset utf8      null,
    IncludeShadowBox tinyint unsigned default '0' not null,
    MetaTitle        varchar(255) charset utf8    null,
    PublishDate      datetime                     null,
    MetaImageID      int                          null
)
    charset = latin1;

create index MetaImageID
    on Page_Live (MetaImageID);

create table Page_versions
(
    ID               int auto_increment
        primary key,
    RecordID         int              default 0   not null,
    Version          int              default 0   not null,
    IncludeJquery    tinyint unsigned default '0' not null,
    PageJavaScript   mediumtext charset utf8      null,
    IncludeShadowBox tinyint unsigned default '0' not null,
    MetaTitle        varchar(255) charset utf8    null,
    PublishDate      datetime                     null,
    MetaImageID      int                          null,
    constraint RecordID_Version
        unique (RecordID, Version)
)
    charset = latin1;

create index MetaImageID
    on Page_versions (MetaImageID);

create index RecordID
    on Page_versions (RecordID);

create index Version
    on Page_versions (Version);

create table Paper
(
    ID                int auto_increment
        primary key,
    ClassName         enum ('Paper') charset utf8 default 'Paper' null,
    LastEdited        datetime                                    null,
    Created           datetime                                    null,
    Title             mediumtext charset utf8                     null,
    Subtitle          mediumtext charset utf8                     null,
    Abstract          mediumtext charset utf8                     null,
    Footer            mediumtext charset utf8                     null,
    CreatorID         int                                         null,
    UpdatedByID       int                                         null,
    BackgroundImageID int                                         null
)
    charset = latin1;

create index BackgroundImageID
    on Paper (BackgroundImageID);

create index ClassName
    on Paper (ClassName);

create index CreatorID
    on Paper (CreatorID);

create index UpdatedByID
    on Paper (UpdatedByID);

create table PaperParagraph
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('PaperParagraph', 'PaperParagraphList') charset utf8 default 'PaperParagraph' null,
    LastEdited datetime                                                                            null,
    Created    datetime                                                                            null,
    Type       enum ('P', 'LIST', 'IMG', 'H5', 'H4') charset utf8         default 'P'              null,
    Content    mediumtext charset utf8                                                             null,
    `Order`    int                                                        default 0                not null,
    SectionID  int                                                                                 null
)
    charset = latin1;

create index ClassName
    on PaperParagraph (ClassName);

create index SectionID
    on PaperParagraph (SectionID);

create table PaperParagraphList
(
    ID      int auto_increment
        primary key,
    SubType enum ('UL', 'OL') charset utf8 default 'UL' null
)
    charset = latin1;

create table PaperParagraphListItem
(
    ID                    int auto_increment
        primary key,
    ClassName             enum ('PaperParagraphListItem') charset utf8 default 'PaperParagraphListItem' null,
    LastEdited            datetime                                                                      null,
    Created               datetime                                                                      null,
    SubItemsContainerType enum ('UL', 'OL', 'NONE') charset utf8       default 'NONE'                   null,
    Content               mediumtext charset utf8                                                       null,
    `Order`               int                                          default 0                        not null,
    OwnerID               int                                                                           null,
    ParentID              int                                                                           null
)
    charset = latin1;

create index ClassName
    on PaperParagraphListItem (ClassName);

create index OwnerID
    on PaperParagraphListItem (OwnerID);

create index ParentID
    on PaperParagraphListItem (ParentID);

create table PaperSection
(
    ID              int auto_increment
        primary key,
    ClassName       enum ('PaperSection', 'CaseOfStudy', 'CaseOfStudySection', 'IndexSection') charset utf8 default 'PaperSection' null,
    LastEdited      datetime                                                                                                       null,
    Created         datetime                                                                                                       null,
    Title           mediumtext charset utf8                                                                                        null,
    Subtitle        mediumtext charset utf8                                                                                        null,
    `Order`         int                                                                                     default 0              not null,
    PaperID         int                                                                                                            null,
    ParentSectionID int                                                                                                            null
)
    charset = latin1;

create index ClassName
    on PaperSection (ClassName);

create index PaperID
    on PaperSection (PaperID);

create index ParentSectionID
    on PaperSection (ParentSectionID);

create table PaperTranslator
(
    ID           int auto_increment
        primary key,
    ClassName    enum ('PaperTranslator') charset utf8 default 'PaperTranslator' null,
    LastEdited   datetime                                                        null,
    Created      datetime                                                        null,
    DisplayName  mediumtext charset utf8                                         null,
    LanguageCode mediumtext charset utf8                                         null,
    PaperID      int                                                             null
)
    charset = latin1;

create index ClassName
    on PaperTranslator (ClassName);

create index PaperID
    on PaperTranslator (PaperID);

create table PaperViewerPage
(
    ID      int auto_increment
        primary key,
    PaperID int null
)
    charset = latin1;

create index PaperID
    on PaperViewerPage (PaperID);

create table PaperViewerPage_Live
(
    ID      int auto_increment
        primary key,
    PaperID int null
)
    charset = latin1;

create index PaperID
    on PaperViewerPage_Live (PaperID);

create table PaperViewerPage_versions
(
    ID       int auto_increment
        primary key,
    RecordID int default 0 not null,
    Version  int default 0 not null,
    PaperID  int           null,
    constraint RecordID_Version
        unique (RecordID, Version)
)
    charset = latin1;

create index PaperID
    on PaperViewerPage_versions (PaperID);

create index RecordID
    on PaperViewerPage_versions (RecordID);

create index Version
    on PaperViewerPage_versions (Version);

create table PdfPage
(
    ID       int auto_increment
        primary key,
    Sidebar  mediumtext charset utf8 null,
    SubTitle mediumtext charset utf8 null
)
    charset = latin1;

create table PdfPage_Live
(
    ID       int auto_increment
        primary key,
    Sidebar  mediumtext charset utf8 null,
    SubTitle mediumtext charset utf8 null
)
    charset = latin1;

create table PdfPage_versions
(
    ID       int auto_increment
        primary key,
    RecordID int default 0           not null,
    Version  int default 0           not null,
    Sidebar  mediumtext charset utf8 null,
    SubTitle mediumtext charset utf8 null,
    constraint RecordID_Version
        unique (RecordID, Version)
)
    charset = latin1;

create index RecordID
    on PdfPage_versions (RecordID);

create index Version
    on PdfPage_versions (Version);

create table PermamailTemplate
(
    ID               int auto_increment
        primary key,
    ClassName        enum ('PermamailTemplate') charset utf8 default 'PermamailTemplate' null,
    LastEdited       datetime                                                            null,
    Created          datetime                                                            null,
    Identifier       varchar(50) charset utf8                                            null,
    Subject          varchar(255) charset utf8                                           null,
    `From`           varchar(50) charset utf8                                            null,
    Content          mediumtext charset utf8                                             null,
    TestEmailAddress varchar(50) charset utf8                                            null
)
    charset = latin1;

create index ClassName
    on PermamailTemplate (ClassName);

create index Identifier
    on PermamailTemplate (Identifier);

create table PermamailTemplateVariable
(
    ID                  int auto_increment
        primary key,
    ClassName           enum ('PermamailTemplateVariable') charset utf8 default 'PermamailTemplateVariable' null,
    LastEdited          datetime                                                                            null,
    Created             datetime                                                                            null,
    Variable            varchar(50) charset utf8                                                            null,
    ValueType           enum ('static', 'random', 'query') charset utf8 default 'static'                    null,
    RecordClass         varchar(50) charset utf8                                                            null,
    Value               varchar(50) charset utf8                                                            null,
    Query               varchar(50) charset utf8                                                            null,
    List                tinyint unsigned                                default '0'                         not null,
    PermamailTemplateID int                                                                                 null
)
    charset = latin1;

create index ClassName
    on PermamailTemplateVariable (ClassName);

create index PermamailTemplateID
    on PermamailTemplateVariable (PermamailTemplateID);

create table Permission
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('Permission') charset utf8 default 'Permission' null,
    LastEdited datetime                                              null,
    Created    datetime                                              null,
    Code       varchar(255) charset utf8                             null,
    Arg        int                              default 0            not null,
    Type       int                              default 1            not null,
    GroupID    int                                                   null
)
    charset = latin1;

create index ClassName
    on Permission (ClassName);

create index Code
    on Permission (Code);

create index GroupID
    on Permission (GroupID);

create table PermissionRole
(
    ID                int auto_increment
        primary key,
    ClassName         enum ('PermissionRole') charset utf8 default 'PermissionRole' null,
    LastEdited        datetime                                                      null,
    Created           datetime                                                      null,
    Title             varchar(50) charset utf8                                      null,
    OnlyAdminCanApply tinyint unsigned                     default '0'              not null
)
    charset = latin1;

create index ClassName
    on PermissionRole (ClassName);

create index Title
    on PermissionRole (Title);

create table PermissionRoleCode
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('PermissionRoleCode') charset utf8 default 'PermissionRoleCode' null,
    LastEdited datetime                                                              null,
    Created    datetime                                                              null,
    Code       varchar(50) charset utf8                                              null,
    RoleID     int                                                                   null
)
    charset = latin1;

create index ClassName
    on PermissionRoleCode (ClassName);

create index RoleID
    on PermissionRoleCode (RoleID);

create table PersonalCalendarShareInfo
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('PersonalCalendarShareInfo') charset utf8 default 'PersonalCalendarShareInfo' null,
    LastEdited datetime                                                                            null,
    Created    datetime                                                                            null,
    Hash       varchar(512) charset utf8                                                           null,
    Revoked    tinyint unsigned                                default '0'                         not null,
    SummitID   int                                                                                 null,
    OwnerID    int                                                                                 null
)
    charset = latin1;

create index ClassName
    on PersonalCalendarShareInfo (ClassName);

create index OwnerID
    on PersonalCalendarShareInfo (OwnerID);

create index SummitID
    on PersonalCalendarShareInfo (SummitID);

create table PresentationCategory
(
    ID                      int auto_increment
        primary key,
    ClassName               enum ('PresentationCategory') charset utf8 default 'PresentationCategory' null,
    LastEdited              datetime                                                                  null,
    Created                 datetime                                                                  null,
    Title                   varchar(255) charset utf8                                                 null,
    Description             mediumtext charset utf8                                                   null,
    SessionCount            int                                        default 0                      not null,
    AlternateCount          int                                        default 0                      not null,
    LightningCount          int                                        default 0                      not null,
    LightningAlternateCount int                                        default 0                      not null,
    VotingVisible           tinyint unsigned                           default '0'                    not null,
    ChairVisible            tinyint unsigned                           default '0'                    not null,
    Code                    varchar(5) charset utf8                                                   null,
    Slug                    varchar(255) charset utf8                                                 null,
    SummitID                int                                                                       null,
    Color                   varchar(50)                                                               null,
    IconID                  int                                                                       null,
    `Order`                 int                                        default 1                      not null,
    constraint FK_CFD8AB836018720
        foreign key (IconID) references File (ID)
            on delete cascade
)
    charset = latin1;

create index ClassName
    on PresentationCategory (ClassName);

create index IconID
    on PresentationCategory (IconID);

create index SummitID
    on PresentationCategory (SummitID);

create table PresentationCategoryGroup
(
    ID                            int auto_increment
        primary key,
    ClassName                     enum ('PresentationCategoryGroup', 'PrivatePresentationCategoryGroup') charset utf8 default 'PresentationCategoryGroup' null,
    LastEdited                    datetime                                                                                                                null,
    Created                       datetime                                                                                                                null,
    Name                          mediumtext charset utf8                                                                                                 null,
    Color                         varchar(50) charset utf8                                                                                                null,
    Description                   mediumtext charset utf8                                                                                                 null,
    SummitID                      int                                                                                                                     null,
    MaxUniqueAttendeeVotes        int                                                                                 default 0                           not null,
    BeginAttendeeVotingPeriodDate datetime                                                                                                                null,
    EndAttendeeVotingPeriodDate   datetime                                                                                                                null
)
    charset = latin1;

create index ClassName
    on PresentationCategoryGroup (ClassName);

create index SummitID
    on PresentationCategoryGroup (SummitID);

create table PresentationCategoryGroup_Categories
(
    ID                          int auto_increment
        primary key,
    PresentationCategoryGroupID int default 0 not null,
    PresentationCategoryID      int default 0 not null
)
    charset = latin1;

create index PresentationCategoryGroupID
    on PresentationCategoryGroup_Categories (PresentationCategoryGroupID);

create index PresentationCategoryID
    on PresentationCategoryGroup_Categories (PresentationCategoryID);

create table PresentationCategoryPage
(
    ID                       int auto_increment
        primary key,
    StillUploading           tinyint unsigned default '0' not null,
    FeaturedVideoLabel       mediumtext charset utf8      null,
    FeaturedVideoDescription mediumtext charset utf8      null
)
    charset = latin1;

create table PresentationCategoryPage_Live
(
    ID                       int auto_increment
        primary key,
    StillUploading           tinyint unsigned default '0' not null,
    FeaturedVideoLabel       mediumtext charset utf8      null,
    FeaturedVideoDescription mediumtext charset utf8      null
)
    charset = latin1;

create table PresentationCategoryPage_versions
(
    ID                       int auto_increment
        primary key,
    RecordID                 int              default 0   not null,
    Version                  int              default 0   not null,
    StillUploading           tinyint unsigned default '0' not null,
    FeaturedVideoLabel       mediumtext charset utf8      null,
    FeaturedVideoDescription mediumtext charset utf8      null,
    constraint RecordID_Version
        unique (RecordID, Version)
)
    charset = latin1;

create index RecordID
    on PresentationCategoryPage_versions (RecordID);

create index Version
    on PresentationCategoryPage_versions (Version);

create table PresentationCategory_AllowedTags
(
    ID                     int auto_increment
        primary key,
    PresentationCategoryID int default 0 not null,
    TagID                  int default 0 not null
)
    charset = latin1;

create index PresentationCategoryID
    on PresentationCategory_AllowedTags (PresentationCategoryID);

create index TagID
    on PresentationCategory_AllowedTags (TagID);

create table PresentationCategory_ExtraQuestions
(
    ID                      int auto_increment
        primary key,
    PresentationCategoryID  int default 0 not null,
    TrackQuestionTemplateID int default 0 not null
)
    charset = latin1;

create index PresentationCategoryID
    on PresentationCategory_ExtraQuestions (PresentationCategoryID);

create index TrackQuestionTemplateID
    on PresentationCategory_ExtraQuestions (TrackQuestionTemplateID);

create table PresentationCategory_SummitAccessLevelType
(
    ID                      int auto_increment
        primary key,
    SummitAccessLevelTypeID int null,
    PresentationCategoryID  int null,
    constraint UNIQ_6CFEA5C430A22149EA82A677
        unique (PresentationCategoryID, SummitAccessLevelTypeID)
)
    collate = utf8_unicode_ci;

create index PresentationCategoryID
    on PresentationCategory_SummitAccessLevelType (PresentationCategoryID);

create index SummitAccessLevelTypeID
    on PresentationCategory_SummitAccessLevelType (SummitAccessLevelTypeID);

create table PresentationChangeRequestPushNotification
(
    ID             int auto_increment
        primary key,
    Channel        enum ('TRACKCHAIRS') charset utf8 default 'TRACKCHAIRS' null,
    PresentationID int                                                     null
)
    charset = latin1;

create index PresentationID
    on PresentationChangeRequestPushNotification (PresentationID);

create table PresentationCreatorNotificationEmailRequest
(
    ID             int auto_increment
        primary key,
    PresentationID int null
)
    charset = latin1;

create index PresentationID
    on PresentationCreatorNotificationEmailRequest (PresentationID);

create table PresentationLink
(
    ID   int auto_increment
        primary key,
    Link mediumtext charset utf8 null
)
    charset = latin1;

create table PresentationPage
(
    ID                       int auto_increment
        primary key,
    LegalAgreement           mediumtext charset utf8 null,
    PresentationDeadlineText mediumtext charset utf8 null,
    VideoLegalConsent        mediumtext charset utf8 null,
    PresentationSuccessText  mediumtext charset utf8 null
)
    charset = latin1;

create table PresentationPage_Live
(
    ID                       int auto_increment
        primary key,
    LegalAgreement           mediumtext charset utf8 null,
    PresentationDeadlineText mediumtext charset utf8 null,
    VideoLegalConsent        mediumtext charset utf8 null,
    PresentationSuccessText  mediumtext charset utf8 null
)
    charset = latin1;

create table PresentationPage_versions
(
    ID                       int auto_increment
        primary key,
    RecordID                 int default 0           not null,
    Version                  int default 0           not null,
    LegalAgreement           mediumtext charset utf8 null,
    PresentationDeadlineText mediumtext charset utf8 null,
    VideoLegalConsent        mediumtext charset utf8 null,
    PresentationSuccessText  mediumtext charset utf8 null,
    constraint RecordID_Version
        unique (RecordID, Version)
)
    charset = latin1;

create index RecordID
    on PresentationPage_versions (RecordID);

create index Version
    on PresentationPage_versions (Version);

create table PresentationRandomVotingList
(
    ID           int auto_increment
        primary key,
    ClassName    enum ('PresentationRandomVotingList') charset utf8 default 'PresentationRandomVotingList' null,
    LastEdited   datetime                                                                                  null,
    Created      datetime                                                                                  null,
    SequenceJSON mediumtext charset utf8                                                                   null,
    SummitID     int                                                                                       null
)
    charset = latin1;

create index ClassName
    on PresentationRandomVotingList (ClassName);

create index SummitID
    on PresentationRandomVotingList (SummitID);

create table PresentationSlide
(
    ID      int auto_increment
        primary key,
    Link    mediumtext charset utf8 null,
    SlideID int                     null
)
    charset = latin1;

create index SlideID
    on PresentationSlide (SlideID);

create table PresentationSpeaker
(
    ID                    int auto_increment
        primary key,
    ClassName             enum ('PresentationSpeaker') charset utf8 default 'PresentationSpeaker' null,
    LastEdited            datetime                                                                null,
    Created               datetime                                                                null,
    FirstName             varchar(100) charset utf8                                               null,
    LastName              varchar(100) charset utf8                                               null,
    Title                 varchar(100) charset utf8                                               null,
    Topic                 varchar(255) charset utf8                                               null,
    Bio                   mediumtext charset utf8                                                 null,
    IRCHandle             varchar(50) charset utf8                                                null,
    TwitterName           varchar(50) charset utf8                                                null,
    AvailableForBureau    tinyint unsigned                          default '0'                   not null,
    FundedTravel          tinyint unsigned                          default '0'                   not null,
    WillingToTravel       tinyint unsigned                          default '0'                   not null,
    Country               varchar(2) charset utf8                                                 null,
    BeenEmailed           tinyint unsigned                          default '0'                   not null,
    WillingToPresentVideo tinyint unsigned                          default '0'                   not null,
    Notes                 mediumtext charset utf8                                                 null,
    CreatedFromAPI        tinyint unsigned                          default '0'                   not null,
    OrgHasCloud           tinyint unsigned                          default '0'                   not null,
    PhotoID               int                                                                     null,
    MemberID              int                                                                     null,
    RegistrationRequestID int                                                                     null,
    BigPhotoID            int                                                                     null,
    Company               tinytext                                                                null,
    PhoneNumber           tinytext                                                                null,
    constraint FK_CAB885EF78E76FB9
        foreign key (BigPhotoID) references File (ID)
            on delete cascade,
    constraint FK_PresentationSpeaker_Member
        foreign key (MemberID) references Member (ID)
            on delete set null
)
    charset = latin1;

create index BigPhotoID
    on PresentationSpeaker (BigPhotoID);

create index ClassName
    on PresentationSpeaker (ClassName);

create index FirstName
    on PresentationSpeaker (FirstName);

create index FirstName_LastName
    on PresentationSpeaker (FirstName, LastName);

create index LastName
    on PresentationSpeaker (LastName);

create index MemberID
    on PresentationSpeaker (MemberID);

create index PhotoID
    on PresentationSpeaker (PhotoID);

create index RegistrationRequestID
    on PresentationSpeaker (RegistrationRequestID);

create table PresentationSpeakerNotificationEmailRequest
(
    ID             int auto_increment
        primary key,
    SpeakerID      int null,
    PresentationID int null
)
    charset = latin1;

create index PresentationID
    on PresentationSpeakerNotificationEmailRequest (PresentationID);

create index SpeakerID
    on PresentationSpeakerNotificationEmailRequest (SpeakerID);

create table PresentationSpeakerSummitAssistanceConfirmationRequest
(
    ID                  int auto_increment
        primary key,
    ClassName           enum ('PresentationSpeakerSummitAssistanceConfirmationRequest') charset utf8 default 'PresentationSpeakerSummitAssistanceConfirmationRequest' null,
    LastEdited          datetime                                                                                                                                      null,
    Created             datetime                                                                                                                                      null,
    OnSitePhoneNumber   mediumtext charset utf8                                                                                                                       null,
    RegisteredForSummit tinyint unsigned                                                             default '0'                                                      not null,
    IsConfirmed         tinyint unsigned                                                             default '0'                                                      not null,
    ConfirmationDate    datetime                                                                                                                                      null,
    ConfirmationHash    mediumtext charset utf8                                                                                                                       null,
    CheckedIn           tinyint unsigned                                                             default '0'                                                      not null,
    SpeakerID           int                                                                                                                                           null,
    SummitID            int                                                                                                                                           null,
    constraint Speaker_Summit
        unique (SpeakerID, SummitID)
)
    charset = latin1;

create index ClassName
    on PresentationSpeakerSummitAssistanceConfirmationRequest (ClassName);

create index SpeakerID
    on PresentationSpeakerSummitAssistanceConfirmationRequest (SpeakerID);

create index SummitID
    on PresentationSpeakerSummitAssistanceConfirmationRequest (SummitID);

create table PresentationSpeakerUploadPresentationMaterialEmail
(
    ID           int auto_increment
        primary key,
    ClassName    enum ('PresentationSpeakerUploadPresentationMaterialEmail') charset utf8 default 'PresentationSpeakerUploadPresentationMaterialEmail' null,
    LastEdited   datetime                                                                                                                              null,
    Created      datetime                                                                                                                              null,
    SentDate     datetime                                                                                                                              null,
    IsRedeemed   tinyint unsigned                                                         default '0'                                                  not null,
    RedeemedDate datetime                                                                                                                              null,
    Hash         mediumtext charset utf8                                                                                                               null,
    SummitID     int                                                                                                                                   null,
    SpeakerID    int                                                                                                                                   null,
    constraint Summit_Speaker_IDX
        unique (SummitID, SpeakerID)
)
    charset = latin1;

create index ClassName
    on PresentationSpeakerUploadPresentationMaterialEmail (ClassName);

create index SpeakerID
    on PresentationSpeakerUploadPresentationMaterialEmail (SpeakerID);

create index SummitID
    on PresentationSpeakerUploadPresentationMaterialEmail (SummitID);

create table PresentationSpeaker_ActiveInvolvements
(
    ID                         int auto_increment
        primary key,
    PresentationSpeakerID      int default 0 not null,
    SpeakerActiveInvolvementID int default 0 not null
)
    charset = latin1;

create index PresentationSpeakerID
    on PresentationSpeaker_ActiveInvolvements (PresentationSpeakerID);

create index SpeakerActiveInvolvementID
    on PresentationSpeaker_ActiveInvolvements (SpeakerActiveInvolvementID);

create table PresentationSpeaker_Languages
(
    ID                    int auto_increment
        primary key,
    PresentationSpeakerID int default 0 not null,
    LanguageID            int default 0 not null
)
    charset = latin1;

create index LanguageID
    on PresentationSpeaker_Languages (LanguageID);

create index PresentationSpeakerID
    on PresentationSpeaker_Languages (PresentationSpeakerID);

create table PresentationSpeaker_OrganizationalRoles
(
    ID                          int auto_increment
        primary key,
    PresentationSpeakerID       int default 0 not null,
    SpeakerOrganizationalRoleID int default 0 not null
)
    charset = latin1;

create index PresentationSpeakerID
    on PresentationSpeaker_OrganizationalRoles (PresentationSpeakerID);

create index SpeakerOrganizationalRoleID
    on PresentationSpeaker_OrganizationalRoles (SpeakerOrganizationalRoleID);

create table PresentationTopic
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('PresentationTopic') charset utf8 default 'PresentationTopic' null,
    LastEdited datetime                                                            null,
    Created    datetime                                                            null,
    Title      varchar(50) charset utf8                                            null
)
    charset = latin1;

create index ClassName
    on PresentationTopic (ClassName);

create table PresentationType_SummitMediaUploadType
(
    ID                      int auto_increment
        primary key,
    PresentationTypeID      int null,
    SummitMediaUploadTypeID int null,
    constraint UNIQ_C33BDDE3962D1E63D70B12DA
        unique (PresentationTypeID, SummitMediaUploadTypeID)
)
    collate = utf8_unicode_ci;

create index PresentationTypeID
    on PresentationType_SummitMediaUploadType (PresentationTypeID);

create index SummitMediaUploadTypeID
    on PresentationType_SummitMediaUploadType (SummitMediaUploadTypeID);

create table PresentationVote
(
    ID             int auto_increment
        primary key,
    ClassName      enum ('PresentationVote') charset utf8 default 'PresentationVote' null,
    LastEdited     datetime                                                          null,
    Created        datetime                                                          null,
    Vote           int                                    default 0                  not null,
    Content        mediumtext charset utf8                                           null,
    MemberID       int                                                               null,
    PresentationID int                                                               null
)
    charset = latin1;

create index ClassName
    on PresentationVote (ClassName);

create index MemberID
    on PresentationVote (MemberID);

create index PresentationID
    on PresentationVote (PresentationID);

create table Presentation_Speakers
(
    ID                    int auto_increment
        primary key,
    PresentationID        int              default 0   not null,
    PresentationSpeakerID int              default 0   not null,
    IsCheckedIn           tinyint unsigned default '0' not null
)
    charset = latin1;

create index PresentationID
    on Presentation_Speakers (PresentationID);

create index PresentationSpeakerID
    on Presentation_Speakers (PresentationSpeakerID);

create table Presentation_Topics
(
    ID                  int auto_increment
        primary key,
    PresentationID      int default 0 not null,
    PresentationTopicID int default 0 not null
)
    charset = latin1;

create index PresentationID
    on Presentation_Topics (PresentationID);

create index PresentationTopicID
    on Presentation_Topics (PresentationTopicID);

create table PricingSchemaType
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('PricingSchemaType') charset utf8 default 'PricingSchemaType' null,
    LastEdited datetime                                                            null,
    Created    datetime                                                            null,
    Type       varchar(50) charset utf8                                            null,
    constraint Type
        unique (Type)
)
    charset = latin1;

create index ClassName
    on PricingSchemaType (ClassName);

create table PrivateCloudService
(
    ID       int auto_increment
        primary key,
    Priority varchar(5) charset utf8 null
)
    charset = latin1;

create table PrivatePresentationCategoryGroup
(
    ID                          int auto_increment
        primary key,
    SubmissionBeginDate         datetime      null,
    SubmissionEndDate           datetime      null,
    MaxSubmissionAllowedPerUser int default 0 not null
)
    charset = latin1;

create table PrivatePresentationCategoryGroup_AllowedGroups
(
    ID                                 int auto_increment
        primary key,
    PrivatePresentationCategoryGroupID int default 0 not null,
    GroupID                            int default 0 not null
)
    charset = latin1;

create index GroupID
    on PrivatePresentationCategoryGroup_AllowedGroups (GroupID);

create index PrivatePresentationCategoryGroupID
    on PrivatePresentationCategoryGroup_AllowedGroups (PrivatePresentationCategoryGroupID);

create table Project
(
    ID          int auto_increment
        primary key,
    ClassName   enum ('Project') charset utf8 default 'Project' null,
    LastEdited  datetime                                        null,
    Created     datetime                                        null,
    Name        varchar(255) charset utf8                       null,
    Description mediumtext charset utf8                         null,
    Codename    mediumtext charset utf8                         null
)
    charset = latin1;

create index ClassName
    on Project (ClassName);

create table PublicCloudPassport
(
    ID            int auto_increment
        primary key,
    ClassName     enum ('PublicCloudPassport') charset utf8 default 'PublicCloudPassport' null,
    LastEdited    datetime                                                                null,
    Created       datetime                                                                null,
    LearnMore     varchar(255) charset utf8                                               null,
    Active        tinyint unsigned                          default '1'                   not null,
    PublicCloudID int                                                                     null
)
    charset = latin1;

create index ClassName
    on PublicCloudPassport (ClassName);

create index PublicCloudID
    on PublicCloudPassport (PublicCloudID);

create table PublicCloudService
(
    ID       int auto_increment
        primary key,
    Priority varchar(5) charset utf8 null
)
    charset = latin1;

create table PushNotificationMessage
(
    ID           int auto_increment
        primary key,
    ClassName    enum ('PushNotificationMessage', 'PresentationChangeRequestPushNotification', 'SummitPushNotification', 'ChatTeamPushNotificationMessage') charset utf8 default 'PushNotificationMessage' null,
    LastEdited   datetime                                                                                                                                                                                  null,
    Created      datetime                                                                                                                                                                                  null,
    Message      mediumtext charset utf8                                                                                                                                                                   null,
    Approved     tinyint unsigned                                                                                                                                        default '0'                       not null,
    IsSent       tinyint unsigned                                                                                                                                        default '0'                       not null,
    SentDate     datetime                                                                                                                                                                                  null,
    Priority     enum ('NORMAL', 'HIGH') charset utf8                                                                                                                    default 'NORMAL'                  null,
    Platform     enum ('MOBILE', 'WEB') charset utf8                                                                                                                     default 'MOBILE'                  null,
    OwnerID      int                                                                                                                                                                                       null,
    ApprovedByID int                                                                                                                                                                                       null
)
    charset = latin1;

create index ApprovedByID
    on PushNotificationMessage (ApprovedByID);

create index ClassName
    on PushNotificationMessage (ClassName);

create index OwnerID
    on PushNotificationMessage (OwnerID);

create table RSVP
(
    ID            int auto_increment
        primary key,
    ClassName     enum ('RSVP') charset utf8                default 'RSVP'    null,
    LastEdited    datetime                                                    null,
    Created       datetime                                                    null,
    BeenEmailed   tinyint unsigned                          default '0'       not null,
    SeatType      enum ('Regular', 'WaitList') charset utf8 default 'Regular' null,
    SubmittedByID int                                                         null,
    EventID       int                                                         null,
    EventUri      varchar(255)                                                null
)
    charset = latin1;

create index ClassName
    on RSVP (ClassName);

create index EventID
    on RSVP (EventID);

create index SubmittedByID
    on RSVP (SubmittedByID);

create table RSVPAnswer
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('RSVPAnswer') charset utf8 default 'RSVPAnswer' null,
    LastEdited datetime                                              null,
    Created    datetime                                              null,
    Value      mediumtext charset utf8                               null,
    QuestionID int                                                   null,
    RSVPID     int                                                   null
)
    charset = latin1;

create index ClassName
    on RSVPAnswer (ClassName);

create index QuestionID
    on RSVPAnswer (QuestionID);

create index RSVPID
    on RSVPAnswer (RSVPID);

create table RSVPCheckBoxListQuestionTemplate
(
    ID int auto_increment
        primary key
)
    charset = latin1;

create table RSVPDropDownQuestionTemplate
(
    ID                int auto_increment
        primary key,
    IsMultiSelect     tinyint unsigned default '0' not null,
    IsCountrySelector tinyint unsigned default '0' not null,
    UseChosenPlugin   tinyint unsigned default '0' not null
)
    charset = latin1;

create table RSVPEventConfiguration
(
    ID                    int auto_increment
        primary key,
    ClassName             enum ('RSVPEventConfiguration') charset utf8 default 'RSVPEventConfiguration' null,
    LastEdited            datetime                                                                      null,
    Created               datetime                                                                      null,
    MaxUserNumber         int                                          default 0                        not null,
    MaxUserWaitListNumber int                                          default 0                        not null,
    SummitEventID         int                                                                           null,
    TemplateID            int                                                                           null
)
    charset = latin1;

create index ClassName
    on RSVPEventConfiguration (ClassName);

create index SummitEventID
    on RSVPEventConfiguration (SummitEventID);

create index TemplateID
    on RSVPEventConfiguration (TemplateID);

create table RSVPLiteralContentQuestionTemplate
(
    ID      int auto_increment
        primary key,
    Content mediumtext charset utf8 null
)
    charset = latin1;

create table RSVPMemberEmailQuestionTemplate
(
    ID int auto_increment
        primary key
)
    charset = latin1;

create table RSVPMemberFirstNameQuestionTemplate
(
    ID int auto_increment
        primary key
)
    charset = latin1;

create table RSVPMemberLastNameQuestionTemplate
(
    ID int auto_increment
        primary key
)
    charset = latin1;

create table RSVPMultiValueQuestionTemplate
(
    ID             int auto_increment
        primary key,
    EmptyString    varchar(255) charset utf8 null,
    DefaultValueID int                       null
)
    charset = latin1;

create index DefaultValueID
    on RSVPMultiValueQuestionTemplate (DefaultValueID);

create table RSVPQuestionTemplate
(
    ID             int auto_increment
        primary key,
    ClassName      enum ('RSVPQuestionTemplate', 'RSVPLiteralContentQuestionTemplate', 'RSVPMultiValueQuestionTemplate', 'RSVPCheckBoxListQuestionTemplate', 'RSVPDropDownQuestionTemplate', 'RSVPRadioButtonListQuestionTemplate', 'RSVPSingleValueTemplateQuestion', 'RSVPCheckBoxQuestionTemplate', 'RSVPTextAreaQuestionTemplate', 'RSVPTextBoxQuestionTemplate', 'RSVPMemberEmailQuestionTemplate', 'RSVPMemberFirstNameQuestionTemplate', 'RSVPMemberLastNameQuestionTemplate') charset utf8 default 'RSVPQuestionTemplate' null,
    LastEdited     datetime                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       null,
    Created        datetime                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       null,
    Name           varchar(255) charset utf8                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      null,
    Label          mediumtext charset utf8                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        null,
    `Order`        int                                                                                                                                                                                                                                                                                                                                                                                                                                                                             default 0                      not null,
    Mandatory      tinyint unsigned                                                                                                                                                                                                                                                                                                                                                                                                                                                                default '0'                    not null,
    ReadOnly       tinyint unsigned                                                                                                                                                                                                                                                                                                                                                                                                                                                                default '0'                    not null,
    RSVPTemplateID int                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            null
)
    charset = latin1;

create index ClassName
    on RSVPQuestionTemplate (ClassName);

create index RSVPTemplateID
    on RSVPQuestionTemplate (RSVPTemplateID);

create table RSVPQuestionTemplate_DependsOn
(
    ID                      int auto_increment
        primary key,
    RSVPQuestionTemplateID  int                                          default 0         not null,
    ChildID                 int                                          default 0         not null,
    ValueID                 int                                          default 0         not null,
    Operator                enum ('Equal', 'Not-Equal') charset utf8     default 'Equal'   null,
    Visibility              enum ('Visible', 'Not-Visible') charset utf8 default 'Visible' null,
    BooleanOperatorOnValues enum ('And', 'Or') charset utf8              default 'And'     null,
    DefaultValue            varchar(254) charset utf8                                      null
)
    charset = latin1;

create index ChildID
    on RSVPQuestionTemplate_DependsOn (ChildID);

create index RSVPQuestionTemplateID
    on RSVPQuestionTemplate_DependsOn (RSVPQuestionTemplateID);

create table RSVPQuestionValueTemplate
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('RSVPQuestionValueTemplate') charset utf8 default 'RSVPQuestionValueTemplate' null,
    LastEdited datetime                                                                            null,
    Created    datetime                                                                            null,
    Value      varchar(255) charset utf8                                                           null,
    `Order`    int                                             default 0                           not null,
    Label      mediumtext charset utf8                                                             null,
    OwnerID    int                                                                                 null
)
    charset = latin1;

create index ClassName
    on RSVPQuestionValueTemplate (ClassName);

create index OwnerID
    on RSVPQuestionValueTemplate (OwnerID);

create table RSVPRadioButtonListQuestionTemplate
(
    ID int auto_increment
        primary key
)
    charset = latin1;

create table RSVPSingleValueTemplateQuestion
(
    ID           int auto_increment
        primary key,
    InitialValue mediumtext charset utf8 null
)
    charset = latin1;

create table RSVPSingleValueTemplateQuestion_ValidationRules
(
    ID                                int auto_increment
        primary key,
    RSVPSingleValueTemplateQuestionID int default 0 not null,
    RSVPSingleValueValidationRuleID   int default 0 not null
)
    charset = latin1;

create index RSVPSingleValueTemplateQuestionID
    on RSVPSingleValueTemplateQuestion_ValidationRules (RSVPSingleValueTemplateQuestionID);

create index RSVPSingleValueValidationRuleID
    on RSVPSingleValueTemplateQuestion_ValidationRules (RSVPSingleValueValidationRuleID);

create table RSVPSingleValueValidationRule
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('RSVPSingleValueValidationRule', 'RSVPNumberValidationRule') charset utf8 default 'RSVPSingleValueValidationRule' null,
    LastEdited datetime                                                                                                                null,
    Created    datetime                                                                                                                null,
    Name       varchar(255) charset utf8                                                                                               null,
    Message    mediumtext charset utf8                                                                                                 null,
    constraint Name
        unique (Name)
)
    charset = latin1;

create index ClassName
    on RSVPSingleValueValidationRule (ClassName);

create table RSVPTemplate
(
    ID          int auto_increment
        primary key,
    ClassName   enum ('RSVPTemplate') charset utf8 default 'RSVPTemplate' null,
    LastEdited  datetime                                                  null,
    Created     datetime                                                  null,
    Title       varchar(255) charset utf8                                 null,
    Enabled     tinyint unsigned                   default '0'            not null,
    CreatedByID int                                                       null,
    SummitID    int                                                       null
)
    charset = latin1;

create index ClassName
    on RSVPTemplate (ClassName);

create index CreatedByID
    on RSVPTemplate (CreatedByID);

create index SummitID
    on RSVPTemplate (SummitID);

create table RSVPTextAreaQuestionTemplate
(
    ID int auto_increment
        primary key
)
    charset = latin1;

create table RSVPTextBoxQuestionTemplate
(
    ID int auto_increment
        primary key
)
    charset = latin1;

create table RSVP_Emails
(
    ID                  int auto_increment
        primary key,
    RSVPID              int default 0 not null,
    SentEmailSendGridID int default 0 not null
)
    charset = latin1;

create index RSVPID
    on RSVP_Emails (RSVPID);

create index SentEmailSendGridID
    on RSVP_Emails (SentEmailSendGridID);

create table RedeemTicketError
(
    ID                 int auto_increment
        primary key,
    ClassName          enum ('RedeemTicketError') charset utf8 default 'RedeemTicketError' null,
    LastEdited         datetime                                                            null,
    Created            datetime                                                            null,
    ExternalOrderId    varchar(255) charset utf8                                           null,
    ExternalAttendeeId varchar(255) charset utf8                                           null,
    OriginatorID       int                                                                 null,
    OriginalOwnerID    int                                                                 null,
    OriginalTicketID   int                                                                 null
)
    charset = latin1;

create index ClassName
    on RedeemTicketError (ClassName);

create index OriginalOwnerID
    on RedeemTicketError (OriginalOwnerID);

create index OriginalTicketID
    on RedeemTicketError (OriginalTicketID);

create index OriginatorID
    on RedeemTicketError (OriginatorID);

create table RedirectorPage
(
    ID              int auto_increment
        primary key,
    RedirectionType enum ('Internal', 'External') charset utf8 default 'Internal' null,
    ExternalURL     varchar(2083) charset utf8                                    null,
    LinkToID        int                                                           null
)
    charset = latin1;

create index LinkToID
    on RedirectorPage (LinkToID);

create table RedirectorPage_Live
(
    ID              int auto_increment
        primary key,
    RedirectionType enum ('Internal', 'External') charset utf8 default 'Internal' null,
    ExternalURL     varchar(2083) charset utf8                                    null,
    LinkToID        int                                                           null
)
    charset = latin1;

create index LinkToID
    on RedirectorPage_Live (LinkToID);

create table RedirectorPage_versions
(
    ID              int auto_increment
        primary key,
    RecordID        int                                        default 0          not null,
    Version         int                                        default 0          not null,
    RedirectionType enum ('Internal', 'External') charset utf8 default 'Internal' null,
    ExternalURL     varchar(2083) charset utf8                                    null,
    LinkToID        int                                                           null,
    constraint RecordID_Version
        unique (RecordID, Version)
)
    charset = latin1;

create index LinkToID
    on RedirectorPage_versions (LinkToID);

create index RecordID
    on RedirectorPage_versions (RecordID);

create index Version
    on RedirectorPage_versions (Version);

create table RefStackLink
(
    ID                        int auto_increment
        primary key,
    ClassName                 enum ('RefStackLink') charset utf8 default 'RefStackLink' null,
    LastEdited                datetime                                                  null,
    Created                   datetime                                                  null,
    Link                      varchar(255) charset utf8                                 null,
    OpenStackImplementationID int                                                       null
)
    charset = latin1;

create index ClassName
    on RefStackLink (ClassName);

create index OpenStackImplementationID
    on RefStackLink (OpenStackImplementationID);

create table Region
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('Region') charset utf8 default 'Region' null,
    LastEdited datetime                                      null,
    Created    datetime                                      null,
    Name       varchar(50) charset utf8                      null,
    constraint Name
        unique (Name)
)
    charset = latin1;

create index ClassName
    on Region (ClassName);

create table RegionalSupport
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('RegionalSupport') charset utf8 default 'RegionalSupport' null,
    LastEdited datetime                                                        null,
    Created    datetime                                                        null,
    `Order`    int                                   default 0                 not null,
    RegionID   int                                                             null,
    ServiceID  int                                                             null,
    constraint Region_Service
        unique (RegionID, ServiceID)
)
    charset = latin1;

create index ClassName
    on RegionalSupport (ClassName);

create index RegionID
    on RegionalSupport (RegionID);

create index ServiceID
    on RegionalSupport (ServiceID);

create table RegionalSupportDraft
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('RegionalSupportDraft') charset utf8 default 'RegionalSupportDraft' null,
    LastEdited datetime                                                                  null,
    Created    datetime                                                                  null,
    `Order`    int                                        default 0                      not null,
    RegionID   int                                                                       null,
    ServiceID  int                                                                       null,
    constraint Region_Service
        unique (RegionID, ServiceID)
)
    charset = latin1;

create index ClassName
    on RegionalSupportDraft (ClassName);

create index RegionID
    on RegionalSupportDraft (RegionID);

create index ServiceID
    on RegionalSupportDraft (ServiceID);

create table RegionalSupportDraft_SupportChannelTypes
(
    ID                     int auto_increment
        primary key,
    RegionalSupportDraftID int default 0            not null,
    SupportChannelTypeID   int default 0            not null,
    Data                   varchar(50) charset utf8 null
)
    charset = latin1;

create index RegionalSupportDraftID
    on RegionalSupportDraft_SupportChannelTypes (RegionalSupportDraftID);

create index SupportChannelTypeID
    on RegionalSupportDraft_SupportChannelTypes (SupportChannelTypeID);

create table RegionalSupport_SupportChannelTypes
(
    ID                   int auto_increment
        primary key,
    RegionalSupportID    int default 0            not null,
    SupportChannelTypeID int default 0            not null,
    Data                 varchar(50) charset utf8 null
)
    charset = latin1;

create index RegionalSupportID
    on RegionalSupport_SupportChannelTypes (RegionalSupportID);

create index SupportChannelTypeID
    on RegionalSupport_SupportChannelTypes (SupportChannelTypeID);

create table RegionalSupportedCompanyService
(
    ID int auto_increment
        primary key
)
    charset = latin1;

create table ReleaseCycleContributor
(
    ID          int auto_increment
        primary key,
    ClassName   enum ('ReleaseCycleContributor') charset utf8 default 'ReleaseCycleContributor' null,
    LastEdited  datetime                                                                        null,
    Created     datetime                                                                        null,
    FirstName   varchar(255) charset utf8                                                       null,
    LastName    varchar(255) charset utf8                                                       null,
    LastCommit  datetime                                                                        null,
    FirstCommit datetime                                                                        null,
    Email       varchar(255) charset utf8                                                       null,
    IRCHandle   varchar(100) charset utf8                                                       null,
    CommitCount int                                           default 0                         not null,
    ExtraEmails mediumtext charset utf8                                                         null,
    MemberID    int                                                                             null,
    ReleaseID   int                                                                             null
)
    charset = latin1;

create index ClassName
    on ReleaseCycleContributor (ClassName);

create index MemberID
    on ReleaseCycleContributor (MemberID);

create index ReleaseID
    on ReleaseCycleContributor (ReleaseID);

create table RemoteCloudService
(
    ID                     int auto_increment
        primary key,
    HardwareSpecifications mediumtext charset utf8      null,
    VendorManagedUpgrades  tinyint unsigned default '0' not null,
    PricingModels          mediumtext charset utf8      null,
    PublishedSLAs          mediumtext charset utf8      null
)
    charset = latin1;

create table RemoteCloudServiceDraft
(
    ID                     int auto_increment
        primary key,
    HardwareSpecifications mediumtext charset utf8      null,
    VendorManagedUpgrades  tinyint unsigned default '0' not null,
    PricingModels          mediumtext charset utf8      null,
    PublishedSLAs          mediumtext charset utf8      null
)
    charset = latin1;

create table RestrictedDownloadPage
(
    ID                 int auto_increment
        primary key,
    GuidelinesLogoLink mediumtext charset utf8 null
)
    charset = latin1;

create table RestrictedDownloadPage_Live
(
    ID                 int auto_increment
        primary key,
    GuidelinesLogoLink mediumtext charset utf8 null
)
    charset = latin1;

create table RestrictedDownloadPage_versions
(
    ID                 int auto_increment
        primary key,
    RecordID           int default 0           not null,
    Version            int default 0           not null,
    GuidelinesLogoLink mediumtext charset utf8 null,
    constraint RecordID_Version
        unique (RecordID, Version)
)
    charset = latin1;

create index RecordID
    on RestrictedDownloadPage_versions (RecordID);

create index Version
    on RestrictedDownloadPage_versions (Version);

create table RoomMetricSampleData
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('RoomMetricSampleData') charset utf8 default 'RoomMetricSampleData' null,
    LastEdited datetime                                                                  null,
    Created    datetime                                                                  null,
    Value      double                                                                    null,
    TimeStamp  int                                        default 0                      not null,
    TypeID     int                                                                       null
)
    charset = latin1;

create index ClassName
    on RoomMetricSampleData (ClassName);

create index TypeID
    on RoomMetricSampleData (TypeID);

create table RoomMetricType
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('RoomMetricType') charset utf8                            default 'RoomMetricType' null,
    LastEdited datetime                                                                                 null,
    Created    datetime                                                                                 null,
    Type       enum ('Persons', 'CO2', 'Temperature', 'Humidity') charset utf8 default 'Persons'        null,
    Unit       enum ('units', 'ppm', '°F', '%') charset utf8                   default 'units'          null,
    Endpoint   mediumtext charset utf8                                                                  null,
    RoomID     int                                                                                      null
)
    charset = latin1;

create index ClassName
    on RoomMetricType (ClassName);

create index RoomID
    on RoomMetricType (RoomID);

create table RssNews
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('RssNews') charset utf8 default 'RssNews' null,
    LastEdited datetime                                        null,
    Created    datetime                                        null,
    Date       datetime                                        null,
    Headline   varchar(512) charset utf8                       null,
    Link       varchar(255) charset utf8                       null,
    Category   varchar(255) charset utf8                       null
)
    charset = latin1;

create index ClassName
    on RssNews (ClassName);

create table SchedSpeaker
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('SchedSpeaker') charset utf8 default 'SchedSpeaker' null,
    LastEdited datetime                                                  null,
    Created    datetime                                                  null,
    username   varchar(50) charset utf8                                  null,
    name       varchar(50) charset utf8                                  null,
    email      varchar(50) charset utf8                                  null
)
    charset = latin1;

create index ClassName
    on SchedSpeaker (ClassName);

create table ScheduleCalendarSyncInfo
(
    ID                       int auto_increment
        primary key,
    ClassName                enum ('ScheduleCalendarSyncInfo') charset utf8 default 'ScheduleCalendarSyncInfo' null,
    LastEdited               datetime                                                                          null,
    Created                  datetime                                                                          null,
    ExternalId               varchar(512) charset utf8                                                         null,
    ETag                     varchar(512) charset utf8                                                         null,
    CalendarEventExternalUrl varchar(512) charset utf8                                                         null,
    VCard                    mediumtext charset utf8                                                           null,
    CalendarSyncInfoID       int                                                                               null,
    OwnerID                  int                                                                               null,
    SummitEventID            int                                                                               null,
    LocationID               int                                                                               null,
    constraint Owner_SummitEvent_CalendarSyncInfo_IDX
        unique (OwnerID, SummitEventID, CalendarSyncInfoID)
)
    charset = latin1;

create index CalendarSyncInfoID
    on ScheduleCalendarSyncInfo (CalendarSyncInfoID);

create index ClassName
    on ScheduleCalendarSyncInfo (ClassName);

create index LocationID
    on ScheduleCalendarSyncInfo (LocationID);

create index OwnerID
    on ScheduleCalendarSyncInfo (OwnerID);

create index SummitEventID
    on ScheduleCalendarSyncInfo (SummitEventID);

create table ScheduledSummitLocationBanner
(
    ID        int auto_increment
        primary key,
    StartDate datetime null,
    EndDate   datetime null
)
    charset = latin1;

create table SciencePage
(
    ID         int auto_increment
        primary key,
    AmazonLink varchar(255) charset utf8 null,
    BookPDFID  int                       null,
    PrintPDFID int                       null
)
    charset = latin1;

create index BookPDFID
    on SciencePage (BookPDFID);

create index PrintPDFID
    on SciencePage (PrintPDFID);

create table SciencePage_Live
(
    ID         int auto_increment
        primary key,
    AmazonLink varchar(255) charset utf8 null,
    BookPDFID  int                       null,
    PrintPDFID int                       null
)
    charset = latin1;

create index BookPDFID
    on SciencePage_Live (BookPDFID);

create index PrintPDFID
    on SciencePage_Live (PrintPDFID);

create table SciencePage_versions
(
    ID         int auto_increment
        primary key,
    RecordID   int default 0             not null,
    Version    int default 0             not null,
    AmazonLink varchar(255) charset utf8 null,
    BookPDFID  int                       null,
    PrintPDFID int                       null,
    constraint RecordID_Version
        unique (RecordID, Version)
)
    charset = latin1;

create index BookPDFID
    on SciencePage_versions (BookPDFID);

create index PrintPDFID
    on SciencePage_versions (PrintPDFID);

create index RecordID
    on SciencePage_versions (RecordID);

create index Version
    on SciencePage_versions (Version);

create table SelectionPlan
(
    ID                                             int auto_increment
        primary key,
    ClassName                                      enum ('SelectionPlan') charset utf8 default 'SelectionPlan' null,
    LastEdited                                     datetime                                                    null,
    Created                                        datetime                                                    null,
    Name                                           varchar(255) charset utf8                                   null,
    Enabled                                        tinyint unsigned                    default '1'             not null,
    SubmissionBeginDate                            datetime                                                    null,
    SubmissionEndDate                              datetime                                                    null,
    VotingBeginDate                                datetime                                                    null,
    VotingEndDate                                  datetime                                                    null,
    SelectionBeginDate                             datetime                                                    null,
    SelectionEndDate                               datetime                                                    null,
    MaxSubmissionAllowedPerUser                    int                                 default 0               not null,
    SummitID                                       int                                                         null,
    AllowNewPresentations                          tinyint(1)                          default 1               not null,
    SubmissionPeriodDisclaimer                     longtext                                                    null,
    PresentationCreatorNotificationEmailTemplate   varchar(255)                        default ''              not null,
    PresentationModeratorNotificationEmailTemplate varchar(255)                        default ''              not null,
    PresentationSpeakerNotificationEmailTemplate   varchar(255)                        default ''              not null
)
    charset = latin1;

create index ClassName
    on SelectionPlan (ClassName);

create index SummitID
    on SelectionPlan (SummitID);

create table SelectionPlan_CategoryGroups
(
    ID                          int auto_increment
        primary key,
    SelectionPlanID             int default 0 not null,
    PresentationCategoryGroupID int default 0 not null
)
    charset = latin1;

create index PresentationCategoryGroupID
    on SelectionPlan_CategoryGroups (PresentationCategoryGroupID);

create index SelectionPlanID
    on SelectionPlan_CategoryGroups (SelectionPlanID);

create table SentEmail
(
    ID              int auto_increment
        primary key,
    ClassName       enum ('SentEmail') charset utf8 default 'SentEmail' null,
    LastEdited      datetime                                            null,
    Created         datetime                                            null,
    `To`            varchar(50) charset utf8                            null,
    `From`          varchar(50) charset utf8                            null,
    Subject         varchar(50) charset utf8                            null,
    Body            mediumtext charset utf8                             null,
    CC              mediumtext charset utf8                             null,
    BCC             mediumtext charset utf8                             null,
    SerializedEmail mediumtext charset utf8                             null
)
    charset = latin1;

create index ClassName
    on SentEmail (ClassName);

create index Created
    on SentEmail (Created);

create table SentEmailSendGrid
(
    ID            int auto_increment
        primary key,
    ClassName     enum ('SentEmailSendGrid') charset utf8 default 'SentEmailSendGrid' null,
    LastEdited    datetime                                                            null,
    Created       datetime                                                            null,
    `To`          varchar(255) charset utf8                                           null,
    `From`        varchar(255) charset utf8                                           null,
    Subject       varchar(255) charset utf8                                           null,
    Body          mediumtext charset utf8                                             null,
    CC            mediumtext charset utf8                                             null,
    BCC           mediumtext charset utf8                                             null,
    IsSent        tinyint unsigned                        default '0'                 not null,
    IsPlain       tinyint unsigned                        default '0'                 not null,
    SentDate      datetime                                                            null,
    Attachments   mediumtext charset utf8                                             null,
    CustomHeaders mediumtext charset utf8                                             null
)
    charset = latin1;

create index ClassName
    on SentEmailSendGrid (ClassName);

create table SiteBannerConfigurationSetting
(
    ID                   int auto_increment
        primary key,
    ClassName            enum ('SiteBannerConfigurationSetting') charset utf8                                                         default 'SiteBannerConfigurationSetting' null,
    LastEdited           datetime                                                                                                                                              null,
    Created              datetime                                                                                                                                              null,
    SiteBannerMessage    mediumtext charset utf8                                                                                                                               null,
    SiteBannerButtonText mediumtext charset utf8                                                                                                                               null,
    SiteBannerButtonLink mediumtext charset utf8                                                                                                                               null,
    SiteBannerRank       int                                                                                                          default 0                                not null,
    Language             enum ('English', 'Spanish', 'Italian', 'German', 'Portuguese', 'Chinese', 'Japanese', 'French') charset utf8 default 'English'                        null,
    SiteConfigID         int                                                                                                                                                   null
)
    charset = latin1;

create index ClassName
    on SiteBannerConfigurationSetting (ClassName);

create index SiteConfigID
    on SiteBannerConfigurationSetting (SiteConfigID);

create table SiteConfig
(
    ID                           int auto_increment
        primary key,
    ClassName                    enum ('SiteConfig') charset utf8                                default 'SiteConfig'    null,
    LastEdited                   datetime                                                                                null,
    Created                      datetime                                                                                null,
    Title                        varchar(255) charset utf8                                                               null,
    Tagline                      varchar(255) charset utf8                                                               null,
    Theme                        varchar(255) charset utf8                                                               null,
    CanViewType                  enum ('Anyone', 'LoggedInUsers', 'OnlyTheseUsers') charset utf8 default 'Anyone'        null,
    CanEditType                  enum ('LoggedInUsers', 'OnlyTheseUsers') charset utf8           default 'LoggedInUsers' null,
    CanCreateTopLevelType        enum ('LoggedInUsers', 'OnlyTheseUsers') charset utf8           default 'LoggedInUsers' null,
    DisplaySiteBanner            tinyint unsigned                                                default '0'             not null,
    RegistrationSendMail         tinyint unsigned                                                default '0'             not null,
    RegistrationFromMessage      mediumtext charset utf8                                                                 null,
    RegistrationSubjectMessage   mediumtext charset utf8                                                                 null,
    RegistrationHTMLMessage      mediumtext charset utf8                                                                 null,
    RegistrationPlainTextMessage mediumtext charset utf8                                                                 null,
    OGApplicationID              varchar(255) charset utf8                                                               null,
    OGAdminID                    varchar(255) charset utf8                                                               null
)
    charset = latin1;

create index ClassName
    on SiteConfig (ClassName);

create table SiteConfig_CreateTopLevelGroups
(
    ID           int auto_increment
        primary key,
    SiteConfigID int default 0 not null,
    GroupID      int default 0 not null
)
    charset = latin1;

create index GroupID
    on SiteConfig_CreateTopLevelGroups (GroupID);

create index SiteConfigID
    on SiteConfig_CreateTopLevelGroups (SiteConfigID);

create table SiteConfig_EditorGroups
(
    ID           int auto_increment
        primary key,
    SiteConfigID int default 0 not null,
    GroupID      int default 0 not null
)
    charset = latin1;

create index GroupID
    on SiteConfig_EditorGroups (GroupID);

create index SiteConfigID
    on SiteConfig_EditorGroups (SiteConfigID);

create table SiteConfig_ViewerGroups
(
    ID           int auto_increment
        primary key,
    SiteConfigID int default 0 not null,
    GroupID      int default 0 not null
)
    charset = latin1;

create index GroupID
    on SiteConfig_ViewerGroups (GroupID);

create index SiteConfigID
    on SiteConfig_ViewerGroups (SiteConfigID);

create table SiteTree
(
    ID              int auto_increment
        primary key,
    ClassName       enum ('SiteTree', 'Page', 'AnniversaryPage', 'ArticleHolder', 'ArticlePage', 'BioPage', 'BoardOfDirectorsPage', 'BrandingPage', 'CoaPage', 'CommPage', 'CommunityPageBis', 'CommunityPage', 'CompaniesPage', 'CompanyListPage', 'ConferenceLivePage', 'ConferenceNewsPage', 'ConferencePage', 'ConferenceSubPage', 'DirectAfterLoginPage', 'HallOfInnovation', 'HomePage', 'InteropPage', 'IVotedPage', 'LandingPageCn', 'LandingPage', 'LegalDocumentPage', 'LegalDocumentsHolder', 'LogoDownloadPage', 'LogoGuidelinesPage', 'LogoRightsPage', 'NewCompanyListPage', 'OneColumn', 'OpenStackFoundationStaffPage', 'OpenstackUser', 'OSLogoProgramPage', 'PdfPage', 'PresentationCategoryPage', 'PrimaryLogoPage', 'PrivacyPage', 'ProductPage', 'PTGDynamic', 'PTGfaq', 'PTG', 'RestrictedDownloadPage', 'SponsorsPage', 'StartPageHolder', 'StartPage', 'swagStore', 'TechnicalCommitteePage', 'UserCommitteePage', 'WebBadgeDownloadPage', 'SangriaPage', 'TrackChairsPage', 'SummitVideoApp', 'PresentationVotingPage', 'ErrorPage', 'RedirectorPage', 'VirtualPage', 'COALandingPage', 'COAVerifyPage', 'EventHolder', 'HackathonsPage', 'OpenStackDaysPage', 'SigninPage', 'AboutMascots', 'AnalystLanding', 'AppDevHomePage', 'AutomotiveLandingPage', 'BareMetalPage', 'ContainersPage2', 'ContainersPage', 'EdgeComputingPage', 'EnterpriseBigDataPage', 'EnterpriseForrester', 'EnterpriseHomePage', 'EnterpriseLegacyPage', 'EnterpriseWorkloadPage', 'ISVHomePage', 'LearnPage', 'SciencePage', 'SecurityPage', 'TelecomHomePage', 'MarketingPage', 'EditProfilePage', 'RegistrationPage', 'SpeakerVotingRegistrationPage', 'SoftwareHomePage', 'SoftwareSubPage', 'SpeakerListPage', 'EmailUtilsPage', 'GeneralEventsLandingPage', 'GeneralSummitLandingPage', 'PresentationVideoPage', 'SchedToolsPage', 'SummitPage', 'EventContextPage', 'NewSchedulePage', 'OpenDevStaticVancouverPage', 'PresentationPage', 'StaticSummitAboutPage', 'SummitAboutPage', 'SummitAppReviewPage', 'SummitAppSchedPage', 'SummitAppVenuesPage', 'OpenDevStaticVancouverAppVenuesPage', 'SummitBostonLanding', 'SummitCategoriesPage', 'OpenDevStaticVancouverCategoriesPage', 'SummitConfirmSpeakerPage', 'SummitContextPage', 'SummitFutureLanding', 'EventsFutureLandingPage', 'SummitHighlightsPage', 'SummitHomePage', 'SummitLocationPage', 'OpenDevStaticVancouverLocationPage', 'SummitNewStaticAboutPage', 'SummitOverviewPage', 'SummitQuestionsPage', 'OpenDevStaticVancouverQuestionsPage', 'SummitSpeakersPage', 'SummitSpeakerVotingPage', 'SummitSponsorPage', 'OpenDevStaticVancouverSponsorPage', 'SummitStaticAboutBerlinPage', 'SummitStaticAboutBostonPage', 'SummitStaticAboutPage', 'SummitStaticAcademyPage', 'SummitStaticAustinGuidePage', 'SummitStaticBarcelonaGuidePage', 'SummitStaticBostonCityGuide', 'SummitStaticCategoriesPage', 'SummitStaticDenverPage', 'SummitStaticDiversityPage', 'SummitStaticOpenSourceDays', 'SummitStaticShangaiPage', 'SummitStaticSponsorPage', 'SummitUpdatesPage', 'SummitSimplePage', 'UserStoriesPage', 'UserStoriesStatic', 'ElectionPage', 'ElectionsHolderPage', 'ElectionVoterPage', 'EventRegistrationRequestPage', 'JobHolder', 'JobRegistrationRequestPage', 'MarketPlaceAdminPage', 'MarketPlacePage', 'MarketPlaceDirectoryPage', 'BooksDirectoryPage', 'ConsultantsDirectoryPage', 'DistributionsDirectoryPage', 'MarketPlaceDriverPage', 'PrivateCloudsDirectoryPage', 'PublicCloudsDirectoryPage', 'RemoteCloudsDirectoryPage', 'TrainingDirectoryPage', 'MarketPlaceLandingPage', 'PublicCloudPassportsPage', 'MemberListPage', 'PaperViewerPage', 'SurveyPage', 'UserSurveyPage', 'SurveyReportPage') charset utf8 default 'SiteTree' null,
    LastEdited      datetime                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         null,
    Created         datetime                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         null,
    URLSegment      varchar(255) charset utf8                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        null,
    Title           varchar(255) charset utf8                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        null,
    MenuTitle       varchar(100) charset utf8                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        null,
    Content         mediumtext charset utf8                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          null,
    MetaDescription mediumtext charset utf8                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          null,
    ExtraMeta       mediumtext charset utf8                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          null,
    ShowInMenus     tinyint unsigned                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              default '0'        not null,
    ShowInSearch    tinyint unsigned                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              default '0'        not null,
    Sort            int                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           default 0          not null,
    HasBrokenFile   tinyint unsigned                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              default '0'        not null,
    HasBrokenLink   tinyint unsigned                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              default '0'        not null,
    ReportClass     varchar(50) charset utf8                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         null,
    CanViewType     enum ('Anyone', 'LoggedInUsers', 'OnlyTheseUsers', 'Inherit') charset utf8                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    default 'Inherit'  null,
    CanEditType     enum ('LoggedInUsers', 'OnlyTheseUsers', 'Inherit') charset utf8                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              default 'Inherit'  null,
    Priority        varchar(5) charset utf8                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          null,
    Version         int                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           default 0          not null,
    ParentID        int                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              null
)
    charset = latin1;

create index ClassName
    on SiteTree (ClassName);

create index ParentID
    on SiteTree (ParentID);

create index Sort
    on SiteTree (Sort);

create index URLSegment
    on SiteTree (URLSegment);

create table SiteTree_EditorGroups
(
    ID         int auto_increment
        primary key,
    SiteTreeID int default 0 not null,
    GroupID    int default 0 not null
)
    charset = latin1;

create index GroupID
    on SiteTree_EditorGroups (GroupID);

create index SiteTreeID
    on SiteTree_EditorGroups (SiteTreeID);

create table SiteTree_ImageTracking
(
    ID         int auto_increment
        primary key,
    SiteTreeID int default 0            not null,
    FileID     int default 0            not null,
    FieldName  varchar(50) charset utf8 null
)
    charset = latin1;

create index FileID
    on SiteTree_ImageTracking (FileID);

create index SiteTreeID
    on SiteTree_ImageTracking (SiteTreeID);

create table SiteTree_LinkTracking
(
    ID         int auto_increment
        primary key,
    SiteTreeID int default 0            not null,
    ChildID    int default 0            not null,
    FieldName  varchar(50) charset utf8 null
)
    charset = latin1;

create index ChildID
    on SiteTree_LinkTracking (ChildID);

create index SiteTreeID
    on SiteTree_LinkTracking (SiteTreeID);

create table SiteTree_Live
(
    ID              int auto_increment
        primary key,
    ClassName       enum ('SiteTree', 'Page', 'AnniversaryPage', 'ArticleHolder', 'ArticlePage', 'BioPage', 'BoardOfDirectorsPage', 'BrandingPage', 'CoaPage', 'CommPage', 'CommunityPageBis', 'CommunityPage', 'CompaniesPage', 'CompanyListPage', 'ConferenceLivePage', 'ConferenceNewsPage', 'ConferencePage', 'ConferenceSubPage', 'DirectAfterLoginPage', 'HallOfInnovation', 'HomePage', 'InteropPage', 'IVotedPage', 'LandingPageCn', 'LandingPage', 'LegalDocumentPage', 'LegalDocumentsHolder', 'LogoDownloadPage', 'LogoGuidelinesPage', 'LogoRightsPage', 'NewCompanyListPage', 'OneColumn', 'OpenStackFoundationStaffPage', 'OpenstackUser', 'OSLogoProgramPage', 'PdfPage', 'PresentationCategoryPage', 'PrimaryLogoPage', 'PrivacyPage', 'ProductPage', 'PTGDynamic', 'PTGfaq', 'PTG', 'RestrictedDownloadPage', 'SponsorsPage', 'StartPageHolder', 'StartPage', 'swagStore', 'TechnicalCommitteePage', 'UserCommitteePage', 'WebBadgeDownloadPage', 'SangriaPage', 'TrackChairsPage', 'SummitVideoApp', 'PresentationVotingPage', 'ErrorPage', 'RedirectorPage', 'VirtualPage', 'COALandingPage', 'COAVerifyPage', 'EventHolder', 'HackathonsPage', 'OpenStackDaysPage', 'SigninPage', 'AboutMascots', 'AnalystLanding', 'AppDevHomePage', 'AutomotiveLandingPage', 'BareMetalPage', 'ContainersPage2', 'ContainersPage', 'EdgeComputingPage', 'EnterpriseBigDataPage', 'EnterpriseForrester', 'EnterpriseHomePage', 'EnterpriseLegacyPage', 'EnterpriseWorkloadPage', 'ISVHomePage', 'LearnPage', 'SciencePage', 'SecurityPage', 'TelecomHomePage', 'MarketingPage', 'EditProfilePage', 'RegistrationPage', 'SpeakerVotingRegistrationPage', 'SoftwareHomePage', 'SoftwareSubPage', 'SpeakerListPage', 'EmailUtilsPage', 'GeneralEventsLandingPage', 'GeneralSummitLandingPage', 'PresentationVideoPage', 'SchedToolsPage', 'SummitPage', 'EventContextPage', 'NewSchedulePage', 'OpenDevStaticVancouverPage', 'PresentationPage', 'StaticSummitAboutPage', 'SummitAboutPage', 'SummitAppReviewPage', 'SummitAppSchedPage', 'SummitAppVenuesPage', 'OpenDevStaticVancouverAppVenuesPage', 'SummitBostonLanding', 'SummitCategoriesPage', 'OpenDevStaticVancouverCategoriesPage', 'SummitConfirmSpeakerPage', 'SummitContextPage', 'SummitFutureLanding', 'EventsFutureLandingPage', 'SummitHighlightsPage', 'SummitHomePage', 'SummitLocationPage', 'OpenDevStaticVancouverLocationPage', 'SummitNewStaticAboutPage', 'SummitOverviewPage', 'SummitQuestionsPage', 'OpenDevStaticVancouverQuestionsPage', 'SummitSpeakersPage', 'SummitSpeakerVotingPage', 'SummitSponsorPage', 'OpenDevStaticVancouverSponsorPage', 'SummitStaticAboutBerlinPage', 'SummitStaticAboutBostonPage', 'SummitStaticAboutPage', 'SummitStaticAcademyPage', 'SummitStaticAustinGuidePage', 'SummitStaticBarcelonaGuidePage', 'SummitStaticBostonCityGuide', 'SummitStaticCategoriesPage', 'SummitStaticDenverPage', 'SummitStaticDiversityPage', 'SummitStaticOpenSourceDays', 'SummitStaticShangaiPage', 'SummitStaticSponsorPage', 'SummitUpdatesPage', 'SummitSimplePage', 'UserStoriesPage', 'UserStoriesStatic', 'ElectionPage', 'ElectionsHolderPage', 'ElectionVoterPage', 'EventRegistrationRequestPage', 'JobHolder', 'JobRegistrationRequestPage', 'MarketPlaceAdminPage', 'MarketPlacePage', 'MarketPlaceDirectoryPage', 'BooksDirectoryPage', 'ConsultantsDirectoryPage', 'DistributionsDirectoryPage', 'MarketPlaceDriverPage', 'PrivateCloudsDirectoryPage', 'PublicCloudsDirectoryPage', 'RemoteCloudsDirectoryPage', 'TrainingDirectoryPage', 'MarketPlaceLandingPage', 'PublicCloudPassportsPage', 'MemberListPage', 'PaperViewerPage', 'SurveyPage', 'UserSurveyPage', 'SurveyReportPage') charset utf8 default 'SiteTree' null,
    LastEdited      datetime                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         null,
    Created         datetime                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         null,
    URLSegment      varchar(255) charset utf8                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        null,
    Title           varchar(255) charset utf8                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        null,
    MenuTitle       varchar(100) charset utf8                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        null,
    Content         mediumtext charset utf8                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          null,
    MetaDescription mediumtext charset utf8                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          null,
    ExtraMeta       mediumtext charset utf8                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          null,
    ShowInMenus     tinyint unsigned                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              default '0'        not null,
    ShowInSearch    tinyint unsigned                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              default '0'        not null,
    Sort            int                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           default 0          not null,
    HasBrokenFile   tinyint unsigned                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              default '0'        not null,
    HasBrokenLink   tinyint unsigned                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              default '0'        not null,
    ReportClass     varchar(50) charset utf8                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         null,
    CanViewType     enum ('Anyone', 'LoggedInUsers', 'OnlyTheseUsers', 'Inherit') charset utf8                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    default 'Inherit'  null,
    CanEditType     enum ('LoggedInUsers', 'OnlyTheseUsers', 'Inherit') charset utf8                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              default 'Inherit'  null,
    Priority        varchar(5) charset utf8                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          null,
    Version         int                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           default 0          not null,
    ParentID        int                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              null
)
    charset = latin1;

create index ClassName
    on SiteTree_Live (ClassName);

create index ParentID
    on SiteTree_Live (ParentID);

create index Sort
    on SiteTree_Live (Sort);

create index URLSegment
    on SiteTree_Live (URLSegment);

create table SiteTree_ViewerGroups
(
    ID         int auto_increment
        primary key,
    SiteTreeID int default 0 not null,
    GroupID    int default 0 not null
)
    charset = latin1;

create index GroupID
    on SiteTree_ViewerGroups (GroupID);

create index SiteTreeID
    on SiteTree_ViewerGroups (SiteTreeID);

create table SiteTree_versions
(
    ID              int auto_increment
        primary key,
    RecordID        int                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     default 0          not null,
    Version         int                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              default 0          not null,
    WasPublished    tinyint unsigned                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 default '0'        not null,
    AuthorID        int                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              default 0          not null,
    PublisherID     int                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              default 0          not null,
    ClassName       enum ('SiteTree', 'Page', 'AnniversaryPage', 'ArticleHolder', 'ArticlePage', 'BioPage', 'BoardOfDirectorsPage', 'BrandingPage', 'CoaPage', 'CommPage', 'CommunityPageBis', 'CommunityPage', 'CompaniesPage', 'CompanyListPage', 'ConferenceLivePage', 'ConferenceNewsPage', 'ConferencePage', 'ConferenceSubPage', 'DirectAfterLoginPage', 'HallOfInnovation', 'HomePage', 'InteropPage', 'IVotedPage', 'LandingPageCn', 'LandingPage', 'LegalDocumentPage', 'LegalDocumentsHolder', 'LogoDownloadPage', 'LogoGuidelinesPage', 'LogoRightsPage', 'NewCompanyListPage', 'OneColumn', 'OpenStackFoundationStaffPage', 'OpenstackUser', 'OSLogoProgramPage', 'PdfPage', 'PresentationCategoryPage', 'PrimaryLogoPage', 'PrivacyPage', 'ProductPage', 'PTGDynamic', 'PTGfaq', 'PTG', 'RestrictedDownloadPage', 'SponsorsPage', 'StartPageHolder', 'StartPage', 'swagStore', 'TechnicalCommitteePage', 'UserCommitteePage', 'WebBadgeDownloadPage', 'SangriaPage', 'TrackChairsPage', 'SummitVideoApp', 'PresentationVotingPage', 'ErrorPage', 'RedirectorPage', 'VirtualPage', 'COALandingPage', 'COAVerifyPage', 'EventHolder', 'HackathonsPage', 'OpenStackDaysPage', 'SigninPage', 'AboutMascots', 'AnalystLanding', 'AppDevHomePage', 'AutomotiveLandingPage', 'BareMetalPage', 'ContainersPage2', 'ContainersPage', 'EdgeComputingPage', 'EnterpriseBigDataPage', 'EnterpriseForrester', 'EnterpriseHomePage', 'EnterpriseLegacyPage', 'EnterpriseWorkloadPage', 'ISVHomePage', 'LearnPage', 'SciencePage', 'SecurityPage', 'TelecomHomePage', 'MarketingPage', 'EditProfilePage', 'RegistrationPage', 'SpeakerVotingRegistrationPage', 'SoftwareHomePage', 'SoftwareSubPage', 'SpeakerListPage', 'EmailUtilsPage', 'GeneralEventsLandingPage', 'GeneralSummitLandingPage', 'PresentationVideoPage', 'SchedToolsPage', 'SummitPage', 'EventContextPage', 'NewSchedulePage', 'OpenDevStaticVancouverPage', 'PresentationPage', 'StaticSummitAboutPage', 'SummitAboutPage', 'SummitAppReviewPage', 'SummitAppSchedPage', 'SummitAppVenuesPage', 'OpenDevStaticVancouverAppVenuesPage', 'SummitBostonLanding', 'SummitCategoriesPage', 'OpenDevStaticVancouverCategoriesPage', 'SummitConfirmSpeakerPage', 'SummitContextPage', 'SummitFutureLanding', 'EventsFutureLandingPage', 'SummitHighlightsPage', 'SummitHomePage', 'SummitLocationPage', 'OpenDevStaticVancouverLocationPage', 'SummitNewStaticAboutPage', 'SummitOverviewPage', 'SummitQuestionsPage', 'OpenDevStaticVancouverQuestionsPage', 'SummitSpeakersPage', 'SummitSpeakerVotingPage', 'SummitSponsorPage', 'OpenDevStaticVancouverSponsorPage', 'SummitStaticAboutBerlinPage', 'SummitStaticAboutBostonPage', 'SummitStaticAboutPage', 'SummitStaticAcademyPage', 'SummitStaticAustinGuidePage', 'SummitStaticBarcelonaGuidePage', 'SummitStaticBostonCityGuide', 'SummitStaticCategoriesPage', 'SummitStaticDenverPage', 'SummitStaticDiversityPage', 'SummitStaticOpenSourceDays', 'SummitStaticShangaiPage', 'SummitStaticSponsorPage', 'SummitUpdatesPage', 'SummitSimplePage', 'UserStoriesPage', 'UserStoriesStatic', 'ElectionPage', 'ElectionsHolderPage', 'ElectionVoterPage', 'EventRegistrationRequestPage', 'JobHolder', 'JobRegistrationRequestPage', 'MarketPlaceAdminPage', 'MarketPlacePage', 'MarketPlaceDirectoryPage', 'BooksDirectoryPage', 'ConsultantsDirectoryPage', 'DistributionsDirectoryPage', 'MarketPlaceDriverPage', 'PrivateCloudsDirectoryPage', 'PublicCloudsDirectoryPage', 'RemoteCloudsDirectoryPage', 'TrainingDirectoryPage', 'MarketPlaceLandingPage', 'PublicCloudPassportsPage', 'MemberListPage', 'PaperViewerPage', 'SurveyPage', 'UserSurveyPage', 'SurveyReportPage') charset utf8 default 'SiteTree' null,
    LastEdited      datetime                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            null,
    Created         datetime                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            null,
    URLSegment      varchar(255) charset utf8                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           null,
    Title           varchar(255) charset utf8                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           null,
    MenuTitle       varchar(100) charset utf8                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           null,
    Content         mediumtext charset utf8                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             null,
    MetaDescription mediumtext charset utf8                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             null,
    ExtraMeta       mediumtext charset utf8                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             null,
    ShowInMenus     tinyint unsigned                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 default '0'        not null,
    ShowInSearch    tinyint unsigned                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 default '0'        not null,
    Sort            int                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              default 0          not null,
    HasBrokenFile   tinyint unsigned                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 default '0'        not null,
    HasBrokenLink   tinyint unsigned                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 default '0'        not null,
    ReportClass     varchar(50) charset utf8                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            null,
    CanViewType     enum ('Anyone', 'LoggedInUsers', 'OnlyTheseUsers', 'Inherit') charset utf8                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       default 'Inherit'  null,
    CanEditType     enum ('LoggedInUsers', 'OnlyTheseUsers', 'Inherit') charset utf8                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 default 'Inherit'  null,
    Priority        varchar(5) charset utf8                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             null,
    ParentID        int                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 null
)
    charset = latin1;

create index AuthorID
    on SiteTree_versions (AuthorID);

create index ClassName
    on SiteTree_versions (ClassName);

create index ParentID
    on SiteTree_versions (ParentID);

create index PublisherID
    on SiteTree_versions (PublisherID);

create index RecordID
    on SiteTree_versions (RecordID);

create index RecordID_Version
    on SiteTree_versions (RecordID, Version);

create index Sort
    on SiteTree_versions (Sort);

create index URLSegment
    on SiteTree_versions (URLSegment);

create index Version
    on SiteTree_versions (Version);

create table SoftwareHomePage
(
    ID          int auto_increment
        primary key,
    IntroTitle  mediumtext charset utf8 null,
    IntroText   mediumtext charset utf8 null,
    IntroTitle2 mediumtext charset utf8 null,
    IntroText2  mediumtext charset utf8 null
)
    charset = latin1;

create table SoftwareHomePageSubMenuItem
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('SoftwareHomePageSubMenuItem') charset utf8 default 'SoftwareHomePageSubMenuItem' null,
    LastEdited datetime                                                                                null,
    Created    datetime                                                                                null,
    Label      mediumtext charset utf8                                                                 null,
    Url        mediumtext charset utf8                                                                 null,
    `Order`    int                                               default 0                             not null,
    ParentID   int                                                                                     null
)
    charset = latin1;

create index ClassName
    on SoftwareHomePageSubMenuItem (ClassName);

create index ParentID
    on SoftwareHomePageSubMenuItem (ParentID);

create table SoftwareHomePage_Live
(
    ID          int auto_increment
        primary key,
    IntroTitle  mediumtext charset utf8 null,
    IntroText   mediumtext charset utf8 null,
    IntroTitle2 mediumtext charset utf8 null,
    IntroText2  mediumtext charset utf8 null
)
    charset = latin1;

create table SoftwareHomePage_versions
(
    ID          int auto_increment
        primary key,
    RecordID    int default 0           not null,
    Version     int default 0           not null,
    IntroTitle  mediumtext charset utf8 null,
    IntroText   mediumtext charset utf8 null,
    IntroTitle2 mediumtext charset utf8 null,
    IntroText2  mediumtext charset utf8 null,
    constraint RecordID_Version
        unique (RecordID, Version)
)
    charset = latin1;

create index RecordID
    on SoftwareHomePage_versions (RecordID);

create index Version
    on SoftwareHomePage_versions (Version);

create table SpeakerActiveInvolvement
(
    ID          int auto_increment
        primary key,
    ClassName   enum ('SpeakerActiveInvolvement') charset utf8 default 'SpeakerActiveInvolvement' null,
    LastEdited  datetime                                                                          null,
    Created     datetime                                                                          null,
    Involvement varchar(254) charset utf8                                                         null,
    IsDefault   tinyint unsigned                               default '0'                        not null
)
    charset = latin1;

create index ClassName
    on SpeakerActiveInvolvement (ClassName);

create table SpeakerAnnouncementSummitEmail
(
    ID                        int auto_increment
        primary key,
    ClassName                 enum ('SpeakerAnnouncementSummitEmail') charset utf8                                                                                                                                                          default 'SpeakerAnnouncementSummitEmail' null,
    LastEdited                datetime                                                                                                                                                                                                                                               null,
    Created                   datetime                                                                                                                                                                                                                                               null,
    AnnouncementEmailTypeSent enum ('ACCEPTED', 'REJECTED', 'ALTERNATE', 'ACCEPTED_ALTERNATE', 'ACCEPTED_REJECTED', 'ALTERNATE_REJECTED', 'SECOND_BREAKOUT_REMINDER', 'SECOND_BREAKOUT_REGISTER', 'CREATE_MEMBERSHIP', 'NONE') charset utf8 default 'NONE'                           null,
    AnnouncementEmailSentDate datetime                                                                                                                                                                                                                                               null,
    SpeakerID                 int                                                                                                                                                                                                                                                    null,
    SummitID                  int                                                                                                                                                                                                                                                    null
)
    charset = latin1;

create index ClassName
    on SpeakerAnnouncementSummitEmail (ClassName);

create index SpeakerID
    on SpeakerAnnouncementSummitEmail (SpeakerID);

create index SummitID
    on SpeakerAnnouncementSummitEmail (SummitID);

create table SpeakerContactEmail
(
    ID             int auto_increment
        primary key,
    ClassName      enum ('SpeakerContactEmail') charset utf8 default 'SpeakerContactEmail' null,
    LastEdited     datetime                                                                null,
    Created        datetime                                                                null,
    OrgName        varchar(255) charset utf8                                               null,
    OrgEmail       varchar(255) charset utf8                                               null,
    EventName      varchar(255) charset utf8                                               null,
    Format         varchar(255) charset utf8                                               null,
    Attendance     int                                       default 0                     not null,
    DateOfEvent    varchar(255) charset utf8                                               null,
    Location       varchar(255) charset utf8                                               null,
    Topics         varchar(255) charset utf8                                               null,
    GeneralRequest mediumtext charset utf8                                                 null,
    EmailSent      tinyint unsigned                          default '0'                   not null,
    RecipientID    int                                                                     null
)
    charset = latin1;

create index ClassName
    on SpeakerContactEmail (ClassName);

create index RecipientID
    on SpeakerContactEmail (RecipientID);

create table SpeakerCreationEmailCreationRequest
(
    ID        int auto_increment
        primary key,
    SpeakerID int null
)
    charset = latin1;

create index SpeakerID
    on SpeakerCreationEmailCreationRequest (SpeakerID);

create table SpeakerEditPermissionRequest
(
    ID            int auto_increment
        primary key,
    ClassName     enum ('SpeakerEditPermissionRequest') charset utf8 default 'SpeakerEditPermissionRequest' null,
    LastEdited    datetime                                                                                  null,
    Created       datetime                                                                                  null,
    Approved      tinyint unsigned                                   default '0'                            not null,
    ApprovedDate  datetime                                                                                  null,
    CreatedDate   datetime                                                                                  null,
    Hash          mediumtext charset utf8                                                                   null,
    SpeakerID     int                                                                                       null,
    RequestedByID int                                                                                       null
)
    charset = latin1;

create index ClassName
    on SpeakerEditPermissionRequest (ClassName);

create index RequestedByID
    on SpeakerEditPermissionRequest (RequestedByID);

create index SpeakerID
    on SpeakerEditPermissionRequest (SpeakerID);

create table SpeakerExpertise
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('SpeakerExpertise') charset utf8 default 'SpeakerExpertise' null,
    LastEdited datetime                                                          null,
    Created    datetime                                                          null,
    Expertise  varchar(254) charset utf8                                         null,
    SpeakerID  int                                                               null
)
    charset = latin1;

create index ClassName
    on SpeakerExpertise (ClassName);

create index SpeakerID
    on SpeakerExpertise (SpeakerID);

create table SpeakerOrganizationalRole
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('SpeakerOrganizationalRole') charset utf8 default 'SpeakerOrganizationalRole' null,
    LastEdited datetime                                                                            null,
    Created    datetime                                                                            null,
    Role       varchar(254) charset utf8                                                           null,
    IsDefault  tinyint unsigned                                default '0'                         not null
)
    charset = latin1;

create index ClassName
    on SpeakerOrganizationalRole (ClassName);

create table SpeakerPresentationLink
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('SpeakerPresentationLink') charset utf8 default 'SpeakerPresentationLink' null,
    LastEdited datetime                                                                        null,
    Created    datetime                                                                        null,
    LinkUrl    mediumtext charset utf8                                                         null,
    Title      mediumtext charset utf8                                                         null,
    SpeakerID  int                                                                             null
)
    charset = latin1;

create index ClassName
    on SpeakerPresentationLink (ClassName);

create index SpeakerID
    on SpeakerPresentationLink (SpeakerID);

create table SpeakerRegistrationRequest
(
    ID               int auto_increment
        primary key,
    ClassName        enum ('SpeakerRegistrationRequest') charset utf8 default 'SpeakerRegistrationRequest' null,
    LastEdited       datetime                                                                              null,
    Created          datetime                                                                              null,
    IsConfirmed      tinyint unsigned                                 default '0'                          not null,
    Email            varchar(254) charset utf8                                                             null,
    ConfirmationDate datetime                                                                              null,
    ConfirmationHash mediumtext charset utf8                                                               null,
    ProposerID       int                                                                                   null,
    SpeakerID        int                                                                                   null,
    constraint Email
        unique (Email)
)
    charset = latin1;

create index ClassName
    on SpeakerRegistrationRequest (ClassName);

create index ProposerID
    on SpeakerRegistrationRequest (ProposerID);

create index SpeakerID
    on SpeakerRegistrationRequest (SpeakerID);

create table SpeakerSelectionAnnouncementEmailCreationRequest
(
    ID          int auto_increment
        primary key,
    Type        enum ('ACCEPTED', 'ACCEPTED_ALTERNATE', 'ACCEPTED_REJECTED', 'ALTERNATE', 'ALTERNATE_REJECTED') charset utf8 default 'ACCEPTED' null,
    SpeakerRole enum ('SPEAKER', 'MODERATOR') charset utf8                                                                   default 'SPEAKER'  null,
    PromoCodeID int                                                                                                                             null,
    SpeakerID   int                                                                                                                             null,
    SummitID    int                                                                                                                             null
)
    charset = latin1;

create index PromoCodeID
    on SpeakerSelectionAnnouncementEmailCreationRequest (PromoCodeID);

create index SpeakerID
    on SpeakerSelectionAnnouncementEmailCreationRequest (SpeakerID);

create index SummitID
    on SpeakerSelectionAnnouncementEmailCreationRequest (SummitID);

create table SpeakerSummitState
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('SpeakerSummitState') charset utf8 default 'SpeakerSummitState' null,
    LastEdited datetime                                                              null,
    Created    datetime                                                              null,
    Event      varchar(50) charset utf8                                              null,
    Notes      mediumtext charset utf8                                               null,
    SummitID   int                                                                   null,
    MemberID   int                                                                   null
)
    charset = latin1;

create index ClassName
    on SpeakerSummitState (ClassName);

create index MemberID
    on SpeakerSummitState (MemberID);

create index SummitID
    on SpeakerSummitState (SummitID);

create table SpeakerTravelPreference
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('SpeakerTravelPreference') charset utf8 default 'SpeakerTravelPreference' null,
    LastEdited datetime                                                                        null,
    Created    datetime                                                                        null,
    Country    mediumtext charset utf8                                                         null,
    SpeakerID  int                                                                             null
)
    charset = latin1;

create index ClassName
    on SpeakerTravelPreference (ClassName);

create index SpeakerID
    on SpeakerTravelPreference (SpeakerID);

create table SpokenLanguage
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('SpokenLanguage') charset utf8 default 'SpokenLanguage' null,
    LastEdited datetime                                                      null,
    Created    datetime                                                      null,
    Name       varchar(50) charset utf8                                      null,
    constraint Name
        unique (Name)
)
    charset = latin1;

create index ClassName
    on SpokenLanguage (ClassName);

create table Sponsor_Users
(
    ID        int auto_increment
        primary key,
    SponsorID int default 0 not null,
    MemberID  int default 0 not null
)
    charset = latin1;

create index MemberID
    on Sponsor_Users (MemberID);

create index SponsorID
    on Sponsor_Users (SponsorID);

create table SponsoredProject
(
    ID          int auto_increment
        primary key,
    Created     datetime                                not null,
    LastEdited  datetime                                not null,
    ClassName   varchar(255) default 'SponsoredProject' not null,
    Name        varchar(255)                            not null,
    Description varchar(1024)                           null,
    Slug        varchar(255)                            not null,
    IsActive    tinyint(1)                              not null,
    constraint UNIQ_785938A738AF345C
        unique (Slug),
    constraint UNIQ_785938A7FE11D138
        unique (Name)
)
    collate = utf8_unicode_ci;

create table ProjectSponsorshipType
(
    ID                 int auto_increment
        primary key,
    Created            datetime                                      not null,
    LastEdited         datetime                                      not null,
    ClassName          varchar(255) default 'ProjectSponsorshipType' not null,
    Name               varchar(255)                                  not null,
    Description        varchar(1024)                                 null,
    Slug               varchar(255)                                  not null,
    `Order`            int          default 1                        not null,
    IsActive           tinyint(1)                                    not null,
    SponsoredProjectID int                                           null,
    constraint UNIQ_2F97F88138AF345CD89789D3
        unique (Slug, SponsoredProjectID),
    constraint UNIQ_2F97F881FE11D138D89789D3
        unique (Name, SponsoredProjectID),
    constraint FK_2F97F881D89789D3
        foreign key (SponsoredProjectID) references SponsoredProject (ID)
            on delete cascade
)
    collate = utf8_unicode_ci;

create index SponsoredProjectID
    on ProjectSponsorshipType (SponsoredProjectID);

create table SponsorsPage_Companies
(
    ID              int auto_increment
        primary key,
    SponsorsPageID  int                                                                                           default 0         not null,
    CompanyID       int                                                                                           default 0         not null,
    SponsorshipType enum ('Headline', 'Premier', 'Event', 'Startup', 'InKind', 'Spotlight', 'Media') charset utf8 default 'Startup' null,
    SubmitPageUrl   mediumtext charset utf8                                                                                         null,
    LogoSize        enum ('None', 'Small', 'Medium', 'Large', 'Big') charset utf8                                 default 'None'    null
)
    charset = latin1;

create index CompanyID
    on SponsorsPage_Companies (CompanyID);

create index SponsorsPageID
    on SponsorsPage_Companies (SponsorsPageID);

create table SponsorshipType
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('SponsorshipType') charset utf8                 default 'SponsorshipType' null,
    LastEdited datetime                                                                        null,
    Created    datetime                                                                        null,
    Name       varchar(50) charset utf8                                                        null,
    Label      varchar(50) charset utf8                                                        null,
    `Order`    int                                                   default 0                 not null,
    Size       enum ('Small', 'Medium', 'Large', 'Big') charset utf8 default 'Medium'          null
)
    charset = latin1;

create table Sponsor
(
    ID                int auto_increment
        primary key,
    ClassName         enum ('Sponsor') charset utf8 default 'Sponsor' null,
    LastEdited        datetime                                        null,
    Created           datetime                                        null,
    SubmitPageUrl     mediumtext charset utf8                         null,
    `Order`           int                           default 0         not null,
    CompanyID         int                                             null,
    SponsorshipTypeID int                                             null,
    SummitID          int                                             null,
    constraint FK_SponsorCompany
        foreign key (CompanyID) references Company (ID)
            on delete set null,
    constraint FK_SponsorSponsorshipType
        foreign key (SponsorshipTypeID) references SponsorshipType (ID)
            on delete set null
)
    charset = latin1;

create index ClassName
    on Sponsor (ClassName);

create index CompanyID
    on Sponsor (CompanyID);

create index SponsorshipTypeID
    on Sponsor (SponsorshipTypeID);

create index SummitID
    on Sponsor (SummitID);

create table SponsorUserInfoGrant
(
    ID            int auto_increment
        primary key,
    Created       datetime                                                                                      null,
    LastEdited    datetime                                                                                      null,
    ClassName     enum ('SponsorUserInfoGrant', 'SponsorBadgeScan') charset utf8 default 'SponsorUserInfoGrant' null,
    AllowedUserID int                                                                                           null,
    SponsorID     int                                                                                           null,
    constraint FK_39DC8CF694CE1A1A
        foreign key (SponsorID) references Sponsor (ID)
            on delete cascade,
    constraint FK_39DC8CF6A293D583
        foreign key (AllowedUserID) references Member (ID)
            on delete cascade
)
    collate = utf8_unicode_ci;

create table SponsorBadgeScan
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('SponsorBadgeScan') charset utf8 default 'SponsorBadgeScan' null,
    LastEdited datetime                                                          null,
    Created    datetime                                                          null,
    QRCode     varchar(255) charset utf8                                         null,
    ScanDate   datetime                                                          null,
    UserID     int                                                               null,
    BadgeID    int                                                               null,
    Notes      varchar(1024)                                                     null,
    constraint FK_SponsorBadgeScan_SponsorUserInfoGrant
        foreign key (ID) references SponsorUserInfoGrant (ID)
            on delete cascade
)
    charset = latin1;

create index BadgeID
    on SponsorBadgeScan (BadgeID);

create index ClassName
    on SponsorBadgeScan (ClassName);

create index UserID
    on SponsorBadgeScan (UserID);

create index AllowedUserID
    on SponsorUserInfoGrant (AllowedUserID);

create index ClassName
    on SponsorUserInfoGrant (ClassName);

create index SponsorID
    on SponsorUserInfoGrant (SponsorID);

create index ClassName
    on SponsorshipType (ClassName);

create table StartPage
(
    ID      int auto_increment
        primary key,
    Summary mediumtext charset utf8 null
)
    charset = latin1;

create table StartPage_Live
(
    ID      int auto_increment
        primary key,
    Summary mediumtext charset utf8 null
)
    charset = latin1;

create table StartPage_versions
(
    ID       int auto_increment
        primary key,
    RecordID int default 0           not null,
    Version  int default 0           not null,
    Summary  mediumtext charset utf8 null,
    constraint RecordID_Version
        unique (RecordID, Version)
)
    charset = latin1;

create index RecordID
    on StartPage_versions (RecordID);

create index Version
    on StartPage_versions (Version);

create table SubQuestionRule
(
    ID                   int unsigned auto_increment
        primary key,
    Created              datetime                                         not null,
    LastEdited           datetime                                         not null,
    Visibility           enum ('Visible', 'NotVisible') default 'Visible' null,
    VisibilityCondition  enum ('Equal', 'NotEqual')     default 'Equal'   null,
    AnswerValues         longtext                                         not null,
    AnswerValuesOperator enum ('Or', 'And')             default 'Or'      null,
    ParentQuestionID     int                                              not null,
    SubQuestionID        int                                              not null,
    constraint UNIQ_B025D976949CB82CD39BE1F4
        unique (ParentQuestionID, SubQuestionID),
    constraint FK_SubQuestionRule_ParentQuestion
        foreign key (ParentQuestionID) references ExtraQuestionType (ID)
            on delete cascade,
    constraint FK_SubQuestionRule_SubQuestion
        foreign key (SubQuestionID) references ExtraQuestionType (ID)
            on delete cascade
)
    collate = utf8_unicode_ci;

create index ParentQuestionID
    on SubQuestionRule (ParentQuestionID);

create index SubQuestionID
    on SubQuestionRule (SubQuestionID);

create table Submitter
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('Submitter') charset utf8 default 'Submitter' null,
    LastEdited datetime                                            null,
    Created    datetime                                            null,
    FirstName  varchar(50) charset utf8                            null,
    LastName   varchar(50) charset utf8                            null,
    Email      varchar(50) charset utf8                            null,
    Company    varchar(50) charset utf8                            null,
    Phone      varchar(50) charset utf8                            null
)
    charset = latin1;

create index ClassName
    on Submitter (ClassName);

create table Summit
(
    ID                                                 int auto_increment
        primary key,
    ClassName                                          enum ('Summit') charset utf8 default 'Summit' null,
    LastEdited                                         datetime                                      null,
    Created                                            datetime                                      null,
    Title                                              varchar(50) charset utf8                      null,
    SummitBeginDate                                    datetime                                      null,
    SummitEndDate                                      datetime                                      null,
    RegistrationBeginDate                              datetime                                      null,
    RegistrationEndDate                                datetime                                      null,
    Active                                             tinyint unsigned             default '0'      not null,
    DateLabel                                          varchar(50) charset utf8                      null,
    Link                                               varchar(50) charset utf8                      null,
    Slug                                               varchar(50) charset utf8                      null,
    RegistrationLink                                   mediumtext charset utf8                       null,
    ComingSoonBtnText                                  mediumtext charset utf8                       null,
    SecondaryRegistrationLink                          mediumtext charset utf8                       null,
    SecondaryRegistrationBtnText                       mediumtext charset utf8                       null,
    ExternalEventId                                    mediumtext charset utf8                       null,
    TimeZoneIdentifier                                 varchar(255) charset utf8                     null,
    StartShowingVenuesDate                             datetime                                      null,
    MaxSubmissionAllowedPerUser                        int                          default 0        not null,
    ScheduleDefaultStartDate                           datetime                                      null,
    AvailableOnApi                                     tinyint unsigned             default '0'      not null,
    CalendarSyncName                                   varchar(255) charset utf8                     null,
    CalendarSyncDescription                            mediumtext charset utf8                       null,
    MeetingRoomBookingStartTime                        time                                          null,
    MeetingRoomBookingEndTime                          time                                          null,
    MeetingRoomBookingSlotLength                       int                          default 0        not null,
    MeetingRoomBookingMaxAllowed                       int                          default 0        not null,
    ApiFeedType                                        mediumtext charset utf8                       null,
    ApiFeedUrl                                         mediumtext charset utf8                       null,
    ApiFeedKey                                         mediumtext charset utf8                       null,
    LogoID                                             int                                           null,
    TypeID                                             int                                           null,
    ReAssignTicketTillDate                             datetime                                      null,
    RegistrationDisclaimerContent                      mediumtext charset utf8                       null,
    RegistrationDisclaimerMandatory                    tinyint unsigned             default '0'      not null,
    ExternalRegistrationFeedType                       mediumtext charset utf8                       null,
    ExternalRegistrationFeedApiKey                     mediumtext charset utf8                       null,
    BeginAllowBookingDate                              datetime                                      null,
    EndAllowBookingDate                                datetime                                      null,
    RegistrationReminderEmailsDaysInterval             int                                           null,
    RegistrationSlugPrefix                             varchar(255)                                  null,
    ScheduleDefaultPageUrl                             longtext                                      null,
    ScheduleDefaultEventDetailUrl                      longtext                                      null,
    ScheduleOGSiteName                                 longtext                                      null,
    ScheduleOGImageUrl                                 longtext                                      null,
    ScheduleOGImageSecureUrl                           longtext                                      null,
    ScheduleOGImageWidth                               int                          default 0        not null,
    ScheduleOGImageHeight                              int                          default 0        not null,
    ScheduleFacebookAppId                              longtext                                      null,
    ScheduleIOSAppName                                 longtext                                      null,
    ScheduleIOSAppStoreId                              longtext                                      null,
    ScheduleIOSAppCustomSchema                         longtext                                      null,
    ScheduleAndroidAppName                             longtext                                      null,
    ScheduleAndroidAppPackage                          longtext                                      null,
    ScheduleAndroidAppCustomSchema                     longtext                                      null,
    ScheduleTwitterAppName                             longtext                                      null,
    ScheduleTwitterText                                longtext                                      null,
    DefaultPageUrl                                     longtext                                      null,
    SpeakerConfirmationDefaultPageUrl                  longtext                                      null,
    VirtualSiteUrl                                     varchar(255)                                  null,
    MarketingSiteUrl                                   varchar(255)                                  null,
    MarketingSiteOAuth2ClientId                        varchar(255)                                  null,
    VirtualSiteOAuth2ClientId                          varchar(255)                                  null,
    SupportEmail                                       varchar(255)                                  null,
    RegistrationSendQRAsImageAttachmentOnTicketEmail   tinyint(1)                   default 0        null,
    RegistrationSendTicketAsPDFAttachmentOnTicketEmail tinyint(1)                   default 0        null,
    RegistrationSendTicketEmailAutomatically           tinyint(1)                   default 1        null,
    RegistrationAllowUpdateAttendeeExtraQuestions      tinyint(1)                   default 0        null,
    TimeZoneLabel                                      varchar(255)                                  null,
    RegistrationAllowAutomaticReminderEmails           tinyint(1)                   default 1        not null,
    RegistrationSendOrderEmailAutomatically            tinyint(1)                   default 1        null,
    ExternalRegistrationFeedLastIngestDate             datetime                                      null
)
    charset = latin1;

create table PaymentGatewayProfile
(
    ID              int auto_increment
        primary key,
    Created         datetime                                                                               not null,
    LastEdited      datetime                                                                               not null,
    ClassName       enum ('PaymentGatewayProfile', 'StripePaymentProfile') default 'PaymentGatewayProfile' null,
    ApplicationType enum ('Registration', 'Meetings')                      default 'Registration'          null,
    Provider        enum ('Stripe')                                        default 'Stripe'                null,
    IsActive        tinyint(1)                                                                             not null,
    SummitID        int                                                                                    null,
    constraint FK_DAED06B790CF7278
        foreign key (SummitID) references Summit (ID)
            on delete cascade
)
    collate = utf8_unicode_ci;

create index SummitID
    on PaymentGatewayProfile (SummitID);

create table PresentationActionType
(
    ID         int auto_increment
        primary key,
    Created    datetime                                                         not null,
    LastEdited datetime                                                         not null,
    ClassName  enum ('PresentationActionType') default 'PresentationActionType' null,
    Label      varchar(255)                                                     not null,
    `Order`    int                             default 1                        not null,
    SummitID   int                                                              null,
    constraint UNIQ_CB86755D90CF7278CF667FEC
        unique (SummitID, Label),
    constraint FK_CB86755D90CF7278
        foreign key (SummitID) references Summit (ID)
            on delete cascade
)
    collate = utf8_unicode_ci;

create index SummitID
    on PresentationActionType (SummitID);

create table StripePaymentProfile
(
    ID                   int auto_increment
        primary key,
    IsTestModeEnabled    tinyint(1) default 0 not null,
    LiveSecretKey        longtext             null,
    LivePublishableKey   longtext             null,
    LiveWebHookSecretKey longtext             null,
    LiveWebHookId        longtext             null,
    TestSecretKey        longtext             null,
    TestPublishableKey   longtext             null,
    TestWebHookSecretKey longtext             null,
    TestWebHookId        longtext             null,
    SendEmailReceipt     tinyint(1) default 0 not null,
    constraint FK_1AEAFB5011D3633A
        foreign key (ID) references PaymentGatewayProfile (ID)
            on delete cascade
)
    collate = utf8_unicode_ci;

create index ClassName
    on Summit (ClassName);

create index LogoID
    on Summit (LogoID);

create index TypeID
    on Summit (TypeID);

create table SummitAbstractLocation
(
    ID           int auto_increment
        primary key,
    ClassName    enum ('SummitAbstractLocation', 'SummitGeoLocatedLocation', 'SummitExternalLocation', 'SummitAirport', 'SummitHotel', 'SummitVenue', 'SummitVenueRoom', 'SummitBookableVenueRoom') charset utf8 default 'SummitAbstractLocation' null,
    LastEdited   datetime                                                                                                                                                                                                                         null,
    Created      datetime                                                                                                                                                                                                                         null,
    Name         varchar(255) charset utf8                                                                                                                                                                                                        null,
    Description  mediumtext charset utf8                                                                                                                                                                                                          null,
    `Order`      int                                                                                                                                                                                             default 0                        not null,
    LocationType enum ('External', 'Internal', 'None') charset utf8                                                                                                                                              default 'None'                   null,
    SummitID     int                                                                                                                                                                                                                              null,
    ShortName    varchar(255)                                                                                                                                                                                                                     null
)
    charset = latin1;

create index ClassName
    on SummitAbstractLocation (ClassName);

create index SummitID
    on SummitAbstractLocation (SummitID);

create table SummitAccessLevelType
(
    ID              int auto_increment
        primary key,
    ClassName       enum ('SummitAccessLevelType') charset utf8 default 'SummitAccessLevelType' null,
    LastEdited      datetime                                                                    null,
    Created         datetime                                                                    null,
    Name            varchar(255) charset utf8                                                   null,
    Description     varchar(255) charset utf8                                                   null,
    IsDefault       tinyint unsigned                            default '0'                     not null,
    TemplateContent mediumtext charset utf8                                                     null,
    SummitID        int                                                                         null
)
    charset = latin1;

create index ClassName
    on SummitAccessLevelType (ClassName);

create index SummitID
    on SummitAccessLevelType (SummitID);

create table SummitActivityDate
(
    ID                  int auto_increment
        primary key,
    ClassName           enum ('SummitActivityDate') charset utf8 default 'SummitActivityDate' null,
    LastEdited          datetime                                                              null,
    Created             datetime                                                              null,
    Date                date                                                                  null,
    Description         mediumtext charset utf8                                               null,
    SummitUpdatesPageID int                                                                   null
)
    charset = latin1;

create index ClassName
    on SummitActivityDate (ClassName);

create index SummitUpdatesPageID
    on SummitActivityDate (SummitUpdatesPageID);

create table SummitAddOn
(
    ID                 int auto_increment
        primary key,
    ClassName          enum ('SummitAddOn') charset utf8 default 'SummitAddOn' null,
    LastEdited         datetime                                                null,
    Created            datetime                                                null,
    Title              mediumtext charset utf8                                 null,
    Cost               mediumtext charset utf8                                 null,
    MaxAvailable       int                               default 0             not null,
    CurrentlyAvailable int                               default 0             not null,
    `Order`            int                               default 0             not null,
    ShowQuantity       tinyint unsigned                  default '0'           not null,
    SummitID           int                                                     null
)
    charset = latin1;

create index ClassName
    on SummitAddOn (ClassName);

create index SummitID
    on SummitAddOn (SummitID);

create table SummitAdministratorPermissionGroup
(
    ID         int auto_increment
        primary key,
    Created    datetime                                                                                              null,
    LastEdited datetime                                                                                              null,
    ClassName  enum ('SummitAdministratorPermissionGroup') charset utf8 default 'SummitAdministratorPermissionGroup' null,
    Title      varchar(255) charset utf8                                                                             null,
    constraint UNIQ_1D5C1CCDEAF7576F
        unique (Title)
)
    collate = utf8_unicode_ci;

create index ClassName
    on SummitAdministratorPermissionGroup (ClassName);

create table SummitAdministratorPermissionGroup_Members
(
    ID                                   int auto_increment
        primary key,
    MemberID                             int default 0 not null,
    SummitAdministratorPermissionGroupID int default 0 not null,
    constraint UNIQ_5CB435FD7B868B2A522B9974
        unique (SummitAdministratorPermissionGroupID, MemberID)
)
    collate = utf8_unicode_ci;

create index MemberID
    on SummitAdministratorPermissionGroup_Members (MemberID);

create index SummitAdministratorPermissionGroupID
    on SummitAdministratorPermissionGroup_Members (SummitAdministratorPermissionGroupID);

create table SummitAdministratorPermissionGroup_Summits
(
    ID                                   int auto_increment
        primary key,
    SummitID                             int default 0 not null,
    SummitAdministratorPermissionGroupID int default 0 not null,
    constraint UNIQ_6FA09E417B868B2A90CF7278
        unique (SummitAdministratorPermissionGroupID, SummitID)
)
    collate = utf8_unicode_ci;

create index SummitAdministratorPermissionGroupID
    on SummitAdministratorPermissionGroup_Summits (SummitAdministratorPermissionGroupID);

create index SummitID
    on SummitAdministratorPermissionGroup_Summits (SummitID);

create table SummitAirport
(
    ID   int auto_increment
        primary key,
    Type enum ('International', 'Domestic') charset utf8 default 'International' null
)
    charset = latin1;

create table SummitAppSchedPage
(
    ID                  int auto_increment
        primary key,
    EnableMobileSupport tinyint unsigned default '0' not null
)
    charset = latin1;

create table SummitAppSchedPage_Live
(
    ID                  int auto_increment
        primary key,
    EnableMobileSupport tinyint unsigned default '0' not null
)
    charset = latin1;

create table SummitAppSchedPage_versions
(
    ID                  int auto_increment
        primary key,
    RecordID            int              default 0   not null,
    Version             int              default 0   not null,
    EnableMobileSupport tinyint unsigned default '0' not null,
    constraint RecordID_Version
        unique (RecordID, Version)
)
    charset = latin1;

create index RecordID
    on SummitAppSchedPage_versions (RecordID);

create index Version
    on SummitAppSchedPage_versions (Version);

create table SummitAttendee
(
    ID                         int auto_increment
        primary key,
    ClassName                  enum ('SummitAttendee') charset utf8 default 'SummitAttendee' null,
    LastEdited                 datetime                                                      null on update CURRENT_TIMESTAMP,
    Created                    datetime                                                      null,
    SharedContactInfo          tinyint unsigned                     default '0'              not null,
    SummitHallCheckedIn        tinyint unsigned                     default '0'              not null,
    SummitHallCheckedInDate    datetime                                                      null,
    MemberID                   int                                                           null,
    SummitID                   int                                                           null,
    FirstName                  varchar(255) charset utf8                                     null,
    Surname                    varchar(255) charset utf8                                     null,
    ExternalId                 varchar(255) charset utf8                                     null,
    Email                      varchar(100) charset utf8                                     not null,
    DisclaimerAcceptedDate     datetime                                                      null,
    Company                    varchar(255) charset utf8                                     null,
    CompanyID                  int                                                           null,
    Status                     enum ('Incomplete', 'Complete')      default 'Incomplete'     null,
    LastReminderEmailSentDate  datetime                                                      null,
    AdminNotes                 varchar(1024)                                                 null,
    SummitVirtualCheckedInDate datetime                                                      null,
    InvitationEmailSentDate    datetime                                                      null,
    PublicEditionEmailSentDate datetime                                                      null,
    constraint SummitAttendee_Email_SummitID
        unique (SummitID, Email),
    constraint SummitAttendee_Member_Summit
        unique (MemberID, SummitID)
)
    charset = latin1;

create index ClassName
    on SummitAttendee (ClassName);

create index MemberID
    on SummitAttendee (MemberID);

create index SummitID
    on SummitAttendee (SummitID);

create table SummitAttendeeBadgePrintRule
(
    ID            int auto_increment
        primary key,
    Created       datetime                                                                                  null,
    LastEdited    datetime                                                                                  null,
    MaxPrintTimes int                                                default 0                              not null,
    GroupID       int                                                                                       null,
    ClassName     enum ('SummitAttendeeBadgePrintRule') charset utf8 default 'SummitAttendeeBadgePrintRule' null,
    constraint FK_ED267F7195291E4
        foreign key (GroupID) references `Group` (ID)
            on delete cascade
)
    collate = utf8_unicode_ci;

create index ClassName
    on SummitAttendeeBadgePrintRule (ClassName);

create index GroupID
    on SummitAttendeeBadgePrintRule (GroupID);

create table SummitAttendeeBadge_Features
(
    ID                       int auto_increment
        primary key,
    SummitAttendeeBadgeID    int default 0 not null,
    SummitBadgeFeatureTypeID int default 0 not null,
    constraint SummitAttendeeBadge_Features_Unique
        unique (SummitAttendeeBadgeID, SummitBadgeFeatureTypeID)
)
    charset = latin1;

create index SummitAttendeeBadgeID
    on SummitAttendeeBadge_Features (SummitAttendeeBadgeID);

create index SummitBadgeFeatureTypeID
    on SummitAttendeeBadge_Features (SummitBadgeFeatureTypeID);

create table SummitAttendeeTicket_Taxes
(
    ID                     int auto_increment
        primary key,
    SummitAttendeeTicketID int           default 0    not null,
    SummitTaxTypeID        int           default 0    not null,
    Amount                 decimal(9, 2) default 0.00 not null
)
    charset = latin1;

create index SummitAttendeeTicketID
    on SummitAttendeeTicket_Taxes (SummitAttendeeTicketID);

create index SummitTaxTypeID
    on SummitAttendeeTicket_Taxes (SummitTaxTypeID);

create table SummitBadgeFeatureType
(
    ID              int auto_increment
        primary key,
    ClassName       enum ('SummitBadgeFeatureType') charset utf8 default 'SummitBadgeFeatureType' null,
    LastEdited      datetime                                                                      null,
    Created         datetime                                                                      null,
    Name            varchar(255) charset utf8                                                     null,
    Description     varchar(255) charset utf8                                                     null,
    TemplateContent mediumtext charset utf8                                                       null,
    SummitID        int                                                                           null,
    ImageID         int                                                                           null,
    constraint FK_506A5DAFE4201A19
        foreign key (ImageID) references File (ID)
            on delete cascade
)
    charset = latin1;

create index ClassName
    on SummitBadgeFeatureType (ClassName);

create index ImageID
    on SummitBadgeFeatureType (ImageID);

create index SummitID
    on SummitBadgeFeatureType (SummitID);

create table SummitBadgeType
(
    ID              int auto_increment
        primary key,
    ClassName       enum ('SummitBadgeType') charset utf8 default 'SummitBadgeType' null,
    LastEdited      datetime                                                        null,
    Created         datetime                                                        null,
    Name            varchar(255) charset utf8                                       null,
    Description     varchar(255) charset utf8                                       null,
    IsDefault       tinyint unsigned                      default '0'               not null,
    TemplateContent mediumtext charset utf8                                         null,
    SummitID        int                                                             null,
    FileID          int                                                             null
)
    charset = latin1;

create table SummitAttendeeBadge
(
    ID          int auto_increment
        primary key,
    ClassName   enum ('SummitAttendeeBadge') charset utf8 default 'SummitAttendeeBadge' null,
    LastEdited  datetime                                                                null,
    Created     datetime                                                                null,
    IsVoid      tinyint unsigned                          default '0'                   not null,
    QRCode      varchar(255) charset utf8                                               null,
    TicketID    int                                                                     null,
    BadgeTypeID int                                                                     null,
    constraint FK_BadgeTypeID
        foreign key (BadgeTypeID) references SummitBadgeType (ID)
            on delete cascade
)
    charset = latin1;

create index BadgeTypeID
    on SummitAttendeeBadge (BadgeTypeID);

create index ClassName
    on SummitAttendeeBadge (ClassName);

create index TicketID
    on SummitAttendeeBadge (TicketID);

create table SummitAttendeeBadgePrint
(
    ID          int auto_increment
        primary key,
    Created     datetime                                                                          null,
    LastEdited  datetime                                                                          null,
    PrintDate   datetime                                                                          null,
    BadgeID     int                                                                               null,
    RequestorID int                                                                               null,
    ClassName   enum ('SummitAttendeeBadgePrint') charset utf8 default 'SummitAttendeeBadgePrint' null,
    constraint FK_A3FFCDAE43A322D3
        foreign key (RequestorID) references Member (ID)
            on delete cascade,
    constraint FK_A3FFCDAE590501E8
        foreign key (BadgeID) references SummitAttendeeBadge (ID)
            on delete cascade
)
    collate = utf8_unicode_ci;

create index BadgeID
    on SummitAttendeeBadgePrint (BadgeID);

create index ClassName
    on SummitAttendeeBadgePrint (ClassName);

create index RequestorID
    on SummitAttendeeBadgePrint (RequestorID);

create index ClassName
    on SummitBadgeType (ClassName);

create index FileID
    on SummitBadgeType (FileID);

create index SummitID
    on SummitBadgeType (SummitID);

create table SummitBadgeType_AccessLevels
(
    ID                      int auto_increment
        primary key,
    SummitBadgeTypeID       int default 0 not null,
    SummitAccessLevelTypeID int default 0 not null
)
    charset = latin1;

create index SummitAccessLevelTypeID
    on SummitBadgeType_AccessLevels (SummitAccessLevelTypeID);

create index SummitBadgeTypeID
    on SummitBadgeType_AccessLevels (SummitBadgeTypeID);

create table SummitBadgeType_BadgeFeatures
(
    ID                       int auto_increment
        primary key,
    SummitBadgeTypeID        int default 0 not null,
    SummitBadgeFeatureTypeID int default 0 not null
)
    charset = latin1;

create index SummitBadgeFeatureTypeID
    on SummitBadgeType_BadgeFeatures (SummitBadgeFeatureTypeID);

create index SummitBadgeTypeID
    on SummitBadgeType_BadgeFeatures (SummitBadgeTypeID);

create table SummitBanner
(
    ID              int auto_increment
        primary key,
    ClassName       enum ('SummitBanner') charset utf8              default 'SummitBanner' null,
    LastEdited      datetime                                                               null,
    Created         datetime                                                               null,
    Name            varchar(255) charset utf8                                              null,
    MainText        mediumtext charset utf8                                                null,
    MainTextColor   varchar(6) charset utf8                                                null,
    SeparatorColor  varchar(6) charset utf8                                                null,
    BackgroundColor varchar(6) charset utf8                                                null,
    ButtonText      mediumtext charset utf8                                                null,
    ButtonLink      varchar(255) charset utf8                                              null,
    ButtonColor     varchar(6) charset utf8                                                null,
    ButtonTextColor varchar(6) charset utf8                                                null,
    SmallText       mediumtext charset utf8                                                null,
    SmallTextColor  varchar(6) charset utf8                                                null,
    Template        enum ('HighlightBar', 'Editorial') charset utf8 default 'HighlightBar' null,
    Enabled         tinyint unsigned                                default '1'            not null,
    LogoID          int                                                                    null,
    PictureID       int                                                                    null,
    ParentPageID    int                                                                    null
)
    charset = latin1;

create index ClassName
    on SummitBanner (ClassName);

create index LogoID
    on SummitBanner (LogoID);

create index ParentPageID
    on SummitBanner (ParentPageID);

create index PictureID
    on SummitBanner (PictureID);

create table SummitBookableVenueRoom
(
    ID           int auto_increment
        primary key,
    TimeSlotCost int default 0           not null,
    Currency     varchar(3) charset utf8 null
)
    charset = latin1;

create table SummitBookableVenueRoomAttributeType
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('SummitBookableVenueRoomAttributeType') charset utf8 default 'SummitBookableVenueRoomAttributeType' null,
    LastEdited datetime                                                                                                  null,
    Created    datetime                                                                                                  null,
    Type       varchar(255) charset utf8                                                                                 null,
    SummitID   int                                                                                                       null,
    constraint SummitID_Type
        unique (SummitID, Type)
)
    charset = latin1;

create index ClassName
    on SummitBookableVenueRoomAttributeType (ClassName);

create index SummitID
    on SummitBookableVenueRoomAttributeType (SummitID);

create table SummitBookableVenueRoomAttributeValue
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('SummitBookableVenueRoomAttributeValue') charset utf8 default 'SummitBookableVenueRoomAttributeValue' null,
    LastEdited datetime                                                                                                    null,
    Created    datetime                                                                                                    null,
    Value      varchar(255) charset utf8                                                                                   null,
    TypeID     int                                                                                                         null,
    constraint TypeID_Value
        unique (TypeID, Value)
)
    charset = latin1;

create index ClassName
    on SummitBookableVenueRoomAttributeValue (ClassName);

create index TypeID
    on SummitBookableVenueRoomAttributeValue (TypeID);

create table SummitBookableVenueRoom_Attributes
(
    ID                                      int auto_increment
        primary key,
    SummitBookableVenueRoomID               int default 0 not null,
    SummitBookableVenueRoomAttributeValueID int default 0 not null
)
    charset = latin1;

create index SummitBookableVenueRoomAttributeValueID
    on SummitBookableVenueRoom_Attributes (SummitBookableVenueRoomAttributeValueID);

create index SummitBookableVenueRoomID
    on SummitBookableVenueRoom_Attributes (SummitBookableVenueRoomID);

create table SummitCategoriesPage
(
    ID          int auto_increment
        primary key,
    HeaderTitle mediumtext charset utf8 null,
    HeaderText  mediumtext charset utf8 null
)
    charset = latin1;

create table SummitCategoriesPage_Live
(
    ID          int auto_increment
        primary key,
    HeaderTitle mediumtext charset utf8 null,
    HeaderText  mediumtext charset utf8 null
)
    charset = latin1;

create table SummitCategoriesPage_versions
(
    ID          int auto_increment
        primary key,
    RecordID    int default 0           not null,
    Version     int default 0           not null,
    HeaderTitle mediumtext charset utf8 null,
    HeaderText  mediumtext charset utf8 null,
    constraint RecordID_Version
        unique (RecordID, Version)
)
    charset = latin1;

create index RecordID
    on SummitCategoriesPage_versions (RecordID);

create index Version
    on SummitCategoriesPage_versions (Version);

create table SummitCategoryChange
(
    ID               int auto_increment
        primary key,
    ClassName        enum ('SummitCategoryChange') charset utf8 default 'SummitCategoryChange' null,
    LastEdited       datetime                                                                  null,
    Created          datetime                                                                  null,
    Comment          varchar(50) charset utf8                                                  null,
    ApprovalDate     datetime                                                                  null,
    Status           int                                        default 0                      not null,
    Reason           mediumtext charset utf8                                                   null,
    NewCategoryID    int                                                                       null,
    OldCategoryID    int                                                                       null,
    PresentationID   int                                                                       null,
    ReqesterID       int                                                                       null,
    OldCatApproverID int                                                                       null,
    NewCatApproverID int                                                                       null,
    AdminApproverID  int                                                                       null
)
    charset = latin1;

create index AdminApproverID
    on SummitCategoryChange (AdminApproverID);

create index ClassName
    on SummitCategoryChange (ClassName);

create index NewCatApproverID
    on SummitCategoryChange (NewCatApproverID);

create index NewCategoryID
    on SummitCategoryChange (NewCategoryID);

create index OldCatApproverID
    on SummitCategoryChange (OldCatApproverID);

create index OldCategoryID
    on SummitCategoryChange (OldCategoryID);

create index PresentationID
    on SummitCategoryChange (PresentationID);

create index ReqesterID
    on SummitCategoryChange (ReqesterID);

create table SummitDocument
(
    ID              int auto_increment
        primary key,
    Created         datetime             not null,
    LastEdited      datetime             not null,
    ClassName       varchar(255)         not null,
    Name            varchar(255)         not null,
    Description     varchar(255)         not null,
    Label           varchar(255)         not null,
    FileID          int                  null,
    SummitID        int                  null,
    ShowAlways      tinyint(1) default 0 not null,
    SelectionPlanID int                  null,
    constraint IDX_SummitDocument_SelectionPlanID
        unique (SelectionPlanID),
    constraint FK_C43764E590CF7278
        foreign key (SummitID) references Summit (ID)
            on delete cascade,
    constraint FK_C43764E593076D5B
        foreign key (FileID) references File (ID)
            on delete cascade,
    constraint FK_SummitDocument_SelectionPlan
        foreign key (SelectionPlanID) references SelectionPlan (ID)
            on delete set null
)
    collate = utf8_unicode_ci;

create index FileID
    on SummitDocument (FileID);

create index SummitID
    on SummitDocument (SummitID);

create table SummitDocument_EventTypes
(
    ID                int auto_increment
        primary key,
    SummitDocumentID  int null,
    SummitEventTypeID int null,
    constraint UNIQ_CCDB2615780505E5DF6E48FA
        unique (SummitDocumentID, SummitEventTypeID)
)
    collate = utf8_unicode_ci;

create index SummitDocumentID
    on SummitDocument_EventTypes (SummitDocumentID);

create index SummitEventTypeID
    on SummitDocument_EventTypes (SummitEventTypeID);

create table SummitEmailFlowType
(
    ID         int auto_increment
        primary key,
    Created    datetime                                                                not null,
    LastEdited datetime                                                                not null,
    ClassName  enum ('SummitEmailFlowType') charset utf8 default 'SummitEmailFlowType' null,
    Name       varchar(255)                                                            not null
)
    collate = utf8_unicode_ci;

create table SummitEmailEventFlowType
(
    ID                             int auto_increment
        primary key,
    Created                        datetime                                                                          not null,
    LastEdited                     datetime                                                                          not null,
    ClassName                      enum ('SummitEmailEventFlowType') charset utf8 default 'SummitEmailEventFlowType' null,
    Slug                           varchar(255)                                                                      not null,
    Name                           varchar(255)                                                                      not null,
    DefaultEmailTemplateIdentifier varchar(255)                                                                      not null,
    SummitEmailFlowTypeID          int                                                                               null,
    constraint FK_CAD6DC9D19C90B6
        foreign key (SummitEmailFlowTypeID) references SummitEmailFlowType (ID)
            on delete cascade
)
    collate = utf8_unicode_ci;

create table SummitEmailEventFlow
(
    ID                         int auto_increment
        primary key,
    Created                    datetime                                                                  not null,
    LastEdited                 datetime                                                                  not null,
    ClassName                  enum ('SummitEmailEventFlow') charset utf8 default 'SummitEmailEventFlow' null,
    EmailTemplateIdentifier    varchar(255)                                                              not null,
    SummitEmailEventFlowTypeID int                                                                       null,
    SummitID                   int                                                                       null,
    constraint FK_3BF9423B38E81E75
        foreign key (SummitEmailEventFlowTypeID) references SummitEmailEventFlowType (ID)
            on delete cascade,
    constraint FK_3BF9423B90CF7278
        foreign key (SummitID) references Summit (ID)
            on delete cascade
)
    collate = utf8_unicode_ci;

create index SummitEmailEventFlowTypeID
    on SummitEmailEventFlow (SummitEmailEventFlowTypeID);

create index SummitID
    on SummitEmailEventFlow (SummitID);

create index SummitEmailFlowTypeID
    on SummitEmailEventFlowType (SummitEmailFlowTypeID);

create table SummitEntityEvent
(
    ID              int auto_increment
        primary key,
    ClassName       enum ('SummitEntityEvent') charset utf8          default 'SummitEntityEvent' null,
    LastEdited      datetime                                                                     null,
    Created         datetime                                                                     null,
    EntityID        int                                              default 0                   not null,
    EntityClassName mediumtext charset utf8                                                      null,
    Type            enum ('UPDATE', 'INSERT', 'DELETE') charset utf8 default 'UPDATE'            null,
    Metadata        mediumtext charset utf8                                                      null,
    SummitID        int                                                                          null,
    OwnerID         int                                                                          null
)
    charset = latin1;

create index ClassName
    on SummitEntityEvent (ClassName);

create index OwnerID
    on SummitEntityEvent (OwnerID);

create index SummitID
    on SummitEntityEvent (SummitID);

create table SummitEventFeedback
(
    ID           int auto_increment
        primary key,
    ClassName    enum ('SummitEventFeedback') charset utf8 default 'SummitEventFeedback' null,
    LastEdited   datetime                                                                null,
    Created      datetime                                                                null,
    Rate         float                                     default 0                     not null,
    Note         mediumtext charset utf8                                                 null,
    Approved     tinyint unsigned                          default '0'                   not null,
    ApprovedDate datetime                                                                null,
    OwnerID      int                                                                     null,
    ApprovedByID int                                                                     null,
    EventID      int                                                                     null
)
    charset = latin1;

create index ApprovedByID
    on SummitEventFeedback (ApprovedByID);

create index ClassName
    on SummitEventFeedback (ClassName);

create index EventID
    on SummitEventFeedback (EventID);

create index OwnerID
    on SummitEventFeedback (OwnerID);

create table SummitEventType
(
    ID                                  int auto_increment
        primary key,
    ClassName                           enum ('SummitEventType', 'PresentationType') charset utf8 default 'SummitEventType' null,
    LastEdited                          datetime                                                                            null,
    Created                             datetime                                                                            null,
    Type                                mediumtext charset utf8                                                             null,
    Color                               mediumtext charset utf8                                                             null,
    BlackoutTimes                       tinyint unsigned                                          default '0'               not null,
    UseSponsors                         tinyint unsigned                                          default '0'               not null,
    AreSponsorsMandatory                tinyint unsigned                                          default '0'               not null,
    AllowsAttachment                    tinyint unsigned                                          default '0'               not null,
    IsDefault                           tinyint unsigned                                          default '0'               not null,
    IsPrivate                           tinyint unsigned                                          default '0'               not null,
    SummitID                            int                                                                                 null,
    AllowsLevel                         tinyint(1)                                                default 0                 not null,
    AllowsPublishingDates               tinyint(1)                                                default 1                 not null,
    AllowsLocation                      tinyint(1)                                                default 1                 not null,
    AllowsLocationAndTimeFrameCollision tinyint(1)                                                default 0                 not null,
    constraint FK_SummitEventType_Summit
        foreign key (SummitID) references Summit (ID)
            on delete cascade
)
    charset = latin1;

create table PresentationType
(
    ID                             int auto_increment
        primary key,
    MaxSpeakers                    int              default 0   not null,
    MinSpeakers                    int              default 0   not null,
    MaxModerators                  int              default 0   not null,
    MinModerators                  int              default 0   not null,
    UseSpeakers                    tinyint unsigned default '0' not null,
    AreSpeakersMandatory           tinyint unsigned default '0' not null,
    UseModerator                   tinyint unsigned default '0' not null,
    IsModeratorMandatory           tinyint unsigned default '0' not null,
    ModeratorLabel                 varchar(255) charset utf8    null,
    ShouldBeAvailableOnCFP         tinyint unsigned default '0' not null,
    AllowAttendeeVote              tinyint(1)       default 0   not null,
    AllowCustomOrdering            tinyint(1)       default 0   not null,
    AllowsSpeakerAndEventCollision tinyint(1)       default 0   not null,
    constraint FK_PresentationType_SummitEventType
        foreign key (ID) references SummitEventType (ID)
            on delete cascade
)
    charset = latin1;

create table SelectionPlan_SummitEventTypes
(
    ID                int auto_increment
        primary key,
    SelectionPlanID   int not null,
    SummitEventTypeID int not null,
    constraint UNIQ_3D40A743B172E6ECDF6E48FA
        unique (SelectionPlanID, SummitEventTypeID),
    constraint FK_3D40A743B172E6EC
        foreign key (SelectionPlanID) references SelectionPlan (ID)
            on delete cascade,
    constraint FK_3D40A743DF6E48FA
        foreign key (SummitEventTypeID) references SummitEventType (ID)
            on delete cascade
)
    collate = utf8_unicode_ci;

create index SelectionPlanID
    on SelectionPlan_SummitEventTypes (SelectionPlanID);

create index SummitEventTypeID
    on SelectionPlan_SummitEventTypes (SummitEventTypeID);

create table SummitEvent
(
    ID                        int auto_increment
        primary key,
    ClassName                 enum ('SummitEvent', 'SummitEventWithFile', 'SummitGroupEvent', 'Presentation') charset utf8 default 'SummitEvent' null,
    LastEdited                datetime                                                                                                           null,
    Created                   datetime                                                                                                           null,
    Title                     mediumtext charset utf8                                                                                            null,
    Abstract                  mediumtext charset utf8                                                                                            null,
    SocialSummary             varchar(100) charset utf8                                                                                          null,
    StartDate                 datetime                                                                                                           null,
    EndDate                   datetime                                                                                                           null,
    Published                 tinyint unsigned                                                                             default '0'           not null,
    PublishedDate             datetime                                                                                                           null,
    AllowFeedBack             tinyint unsigned                                                                             default '0'           not null,
    AvgFeedbackRate           float                                                                                        default 0             not null,
    HeadCount                 int                                                                                          default 0             not null,
    RSVPLink                  mediumtext charset utf8                                                                                            null,
    RSVPMaxUserNumber         int                                                                                          default 0             not null,
    RSVPMaxUserWaitListNumber int                                                                                          default 0             not null,
    Occupancy                 enum ('EMPTY', '25%', '50%', '75%', 'FULL') charset utf8                                     default 'EMPTY'       null,
    ExternalId                mediumtext charset utf8                                                                                            null,
    LocationID                int                                                                                                                null,
    SummitID                  int                                                                                                                null,
    TypeID                    int                                                                                                                null,
    RSVPTemplateID            int                                                                                                                null,
    CategoryID                int                                                                                                                null,
    StreamingUrl              longtext                                                                                                           null,
    EtherpadLink              longtext                                                                                                           null,
    MeetingUrl                longtext                                                                                                           null,
    ImageID                   int                                                                                                                null,
    MuxPlaybackID             longtext                                                                                                           null,
    MuxAssetID                longtext                                                                                                           null,
    Level                     enum ('Beginner', 'Intermediate', 'Advanced', 'N/A') charset utf8                            default 'Beginner'    null,
    CreatedByID               int                                                                                                                null,
    UpdatedByID               int                                                                                                                null,
    StreamingType             varchar(4)                                                                                   default 'LIVE'        not null,
    ShowSponsors              tinyint(1)                                                                                   default 0             not null,
    constraint FK_SummitEvent_CreatedBy
        foreign key (CreatedByID) references Member (ID)
            on delete set null,
    constraint FK_SummitEvent_Image
        foreign key (ImageID) references File (ID)
            on delete cascade,
    constraint FK_SummitEvent_PresentationCategory
        foreign key (CategoryID) references PresentationCategory (ID)
            on delete set null,
    constraint FK_SummitEvent_Summit
        foreign key (SummitID) references Summit (ID)
            on delete set null,
    constraint FK_SummitEvent_SummitAbstractLocation
        foreign key (LocationID) references SummitAbstractLocation (ID)
            on delete set null,
    constraint FK_SummitEvent_SummitEventType
        foreign key (TypeID) references SummitEventType (ID)
            on delete set null,
    constraint FK_Summit_Event_UpdatedBy
        foreign key (UpdatedByID) references Member (ID)
            on delete set null,
    constraint FK_Summit_event_RSVPTemplate
        foreign key (RSVPTemplateID) references RSVPTemplate (ID)
            on delete set null
)
    charset = latin1;

create table Presentation
(
    ID                      int auto_increment
        primary key,
    Status                  varchar(50) charset utf8     null,
    OtherTopic              varchar(50) charset utf8     null,
    Progress                int              default 0   not null,
    Views                   int              default 0   not null,
    BeenEmailed             tinyint unsigned default '0' not null,
    ProblemAddressed        mediumtext charset utf8      null,
    AttendeesExpectedLearnt mediumtext charset utf8      null,
    Legacy                  tinyint unsigned default '0' not null,
    ToRecord                tinyint unsigned default '0' not null,
    AttendingMedia          tinyint unsigned default '0' not null,
    Slug                    varchar(255) charset utf8    null,
    ModeratorID             int                          null,
    SelectionPlanID         int                          null,
    WillAllSpeakersAttend   tinyint(1)       default 0   not null,
    DisclaimerAcceptedDate  datetime                     null,
    CustomOrder             int              default 0   not null,
    constraint FK_PresentationSelectionPlan
        foreign key (SelectionPlanID) references SelectionPlan (ID)
            on delete set null,
    constraint FK_Presentation_Moderator
        foreign key (ModeratorID) references PresentationSpeaker (ID)
            on delete set null,
    constraint FK_Presentation_SummitEvent
        foreign key (ID) references SummitEvent (ID)
            on delete cascade
)
    charset = latin1;

create index ModeratorID
    on Presentation (ModeratorID);

create index SelectionPlanID
    on Presentation (SelectionPlanID);

create index Slug
    on Presentation (Slug);

create table PresentationAction
(
    ID             int auto_increment
        primary key,
    Created        datetime                                                 not null,
    LastEdited     datetime                                                 not null,
    ClassName      enum ('PresentationAction') default 'PresentationAction' null,
    IsCompleted    tinyint(1)                  default 0                    not null,
    TypeID         int                                                      null,
    PresentationID int                                                      null,
    CreatedByID    int                                                      null,
    UpdateByID     int                                                      null,
    constraint UNIQ_717B26A9280A3317A736B16E
        unique (PresentationID, TypeID),
    constraint FK_717B26A9280A3317
        foreign key (PresentationID) references Presentation (ID)
            on delete cascade,
    constraint FK_717B26A9A736B16E
        foreign key (TypeID) references PresentationActionType (ID)
            on delete cascade,
    constraint FK_717B26A9CABFF699
        foreign key (CreatedByID) references Member (ID)
            on delete set null,
    constraint FK_717B26A9CE220AF9
        foreign key (UpdateByID) references Member (ID)
            on delete set null
)
    collate = utf8_unicode_ci;

create index CreatedByID
    on PresentationAction (CreatedByID);

create index PresentationID
    on PresentationAction (PresentationID);

create index TypeID
    on PresentationAction (TypeID);

create index UpdateByID
    on PresentationAction (UpdateByID);

create table PresentationAttendeeVote
(
    ID               int auto_increment
        primary key,
    Created          datetime                                                             not null,
    LastEdited       datetime                                                             not null,
    ClassName        enum ('PresentationAttendeeVote') default 'PresentationAttendeeVote' null,
    PresentationID   int                                                                  null,
    SummitAttendeeID int                                                                  null,
    constraint UNIQ_F3F3F0C5280A3317D008A3A9
        unique (PresentationID, SummitAttendeeID),
    constraint FK_F3F3F0C5280A3317
        foreign key (PresentationID) references Presentation (ID)
            on delete cascade,
    constraint FK_F3F3F0C5D008A3A9
        foreign key (SummitAttendeeID) references SummitAttendee (ID)
            on delete cascade
)
    collate = utf8_unicode_ci;

create index PresentationID
    on PresentationAttendeeVote (PresentationID);

create index SummitAttendeeID
    on PresentationAttendeeVote (SummitAttendeeID);

create table PresentationExtraQuestionAnswer
(
    ID             int auto_increment
        primary key,
    PresentationID int null,
    constraint FK_FFD9217E280A3317
        foreign key (PresentationID) references Presentation (ID)
            on delete cascade,
    constraint JT_PresentationExtraQuestionAnswer_ExtraQuestionAnswer
        foreign key (ID) references ExtraQuestionAnswer (ID)
            on delete cascade
)
    collate = utf8_unicode_ci;

create index PresentationID
    on PresentationExtraQuestionAnswer (PresentationID);

create table PresentationMaterial
(
    ID             int auto_increment
        primary key,
    ClassName      enum ('PresentationMaterial', 'PresentationLink', 'PresentationSlide', 'PresentationVideo', 'PresentationMediaUpload') charset utf8 default 'PresentationMaterial' null,
    LastEdited     datetime                                                                                                                                                           null,
    Created        datetime                                                                                                                                                           null,
    Name           mediumtext charset utf8                                                                                                                                            null,
    Description    mediumtext charset utf8                                                                                                                                            null,
    DisplayOnSite  tinyint unsigned                                                                                                                    default '0'                    not null,
    Featured       tinyint unsigned                                                                                                                    default '0'                    not null,
    `Order`        int                                                                                                                                 default 0                      not null,
    PresentationID int                                                                                                                                                                null,
    constraint FK_PresentationMaterialPresentation
        foreign key (PresentationID) references Presentation (ID)
            on delete cascade
)
    charset = latin1;

create index ClassName
    on PresentationMaterial (ClassName);

create index PresentationID
    on PresentationMaterial (PresentationID);

create table PresentationTrackChairView
(
    ID             int auto_increment
        primary key,
    ClassName      enum ('PresentationTrackChairView') charset utf8 default 'PresentationTrackChairView' null,
    LastEdited     datetime                                                                              null,
    Created        datetime                                                                              null,
    TrackChairID   int                                                                                   null,
    PresentationID int                                                                                   null,
    constraint FK_A376FB63280A3317
        foreign key (PresentationID) references Presentation (ID)
            on delete cascade,
    constraint FK_A376FB6340EBEBB0
        foreign key (TrackChairID) references Member (ID)
            on delete cascade
)
    charset = latin1;

create index ClassName
    on PresentationTrackChairView (ClassName);

create index PresentationID
    on PresentationTrackChairView (PresentationID);

create index TrackChairID
    on PresentationTrackChairView (TrackChairID);

create table PresentationVideo
(
    ID               int auto_increment
        primary key,
    YouTubeID        mediumtext charset utf8      null,
    DateUploaded     datetime                     null,
    Highlighted      tinyint unsigned default '0' not null,
    Views            int              default 0   not null,
    ViewsLastUpdated datetime                     null,
    Processed        tinyint unsigned default '0' not null,
    Priority         varchar(5) charset utf8      null,
    ExternalUrl      varchar(255)                 null,
    constraint FK_PresentationVideoPresentationMaterial
        foreign key (ID) references PresentationMaterial (ID)
            on delete cascade
)
    charset = latin1;

create index CategoryID
    on SummitEvent (CategoryID);

create index ClassName
    on SummitEvent (ClassName);

create index CreatedByID
    on SummitEvent (CreatedByID);

create index ImageID
    on SummitEvent (ImageID);

create index LocationID
    on SummitEvent (LocationID);

create index RSVPTemplateID
    on SummitEvent (RSVPTemplateID);

create index SummitID
    on SummitEvent (SummitID);

create index TypeID
    on SummitEvent (TypeID);

create index UpdatedByID
    on SummitEvent (UpdatedByID);

create index ClassName
    on SummitEventType (ClassName);

create index SummitID
    on SummitEventType (SummitID);

create table SummitEventWithFile
(
    ID           int auto_increment
        primary key,
    AttachmentID int null
)
    charset = latin1;

create index AttachmentID
    on SummitEventWithFile (AttachmentID);

create table SummitEvent_Sponsors
(
    ID            int auto_increment
        primary key,
    SummitEventID int default 0 not null,
    CompanyID     int default 0 not null
)
    charset = latin1;

create index CompanyID
    on SummitEvent_Sponsors (CompanyID);

create index SummitEventID
    on SummitEvent_Sponsors (SummitEventID);

create table SummitEvent_Tags
(
    ID            int auto_increment
        primary key,
    SummitEventID int default 0 not null,
    TagID         int default 0 not null
)
    charset = latin1;

create index SummitEventID
    on SummitEvent_Tags (SummitEventID);

create index TagID
    on SummitEvent_Tags (TagID);

create table SummitExternalLocation
(
    ID       int auto_increment
        primary key,
    Capacity int default 0 not null
)
    charset = latin1;

create table SummitFutureLanding
(
    ID            int auto_increment
        primary key,
    BGImageOffset int default 0           not null,
    IntroText     mediumtext charset utf8 null,
    MainTitle     mediumtext charset utf8 null,
    LocSubtitle   mediumtext charset utf8 null,
    ProspectusUrl mediumtext charset utf8 null,
    RegisterUrl   mediumtext charset utf8 null,
    ShareText     mediumtext charset utf8 null,
    PhotoTitle    mediumtext charset utf8 null,
    PhotoUrl      mediumtext charset utf8 null
)
    charset = latin1;

create table SummitFutureLanding_Live
(
    ID            int auto_increment
        primary key,
    BGImageOffset int default 0           not null,
    IntroText     mediumtext charset utf8 null,
    MainTitle     mediumtext charset utf8 null,
    LocSubtitle   mediumtext charset utf8 null,
    ProspectusUrl mediumtext charset utf8 null,
    RegisterUrl   mediumtext charset utf8 null,
    ShareText     mediumtext charset utf8 null,
    PhotoTitle    mediumtext charset utf8 null,
    PhotoUrl      mediumtext charset utf8 null
)
    charset = latin1;

create table SummitFutureLanding_versions
(
    ID            int auto_increment
        primary key,
    RecordID      int default 0           not null,
    Version       int default 0           not null,
    BGImageOffset int default 0           not null,
    IntroText     mediumtext charset utf8 null,
    MainTitle     mediumtext charset utf8 null,
    LocSubtitle   mediumtext charset utf8 null,
    ProspectusUrl mediumtext charset utf8 null,
    RegisterUrl   mediumtext charset utf8 null,
    ShareText     mediumtext charset utf8 null,
    PhotoTitle    mediumtext charset utf8 null,
    PhotoUrl      mediumtext charset utf8 null,
    constraint RecordID_Version
        unique (RecordID, Version)
)
    charset = latin1;

create index RecordID
    on SummitFutureLanding_versions (RecordID);

create index Version
    on SummitFutureLanding_versions (Version);

create table SummitGeoLocatedLocation
(
    ID              int auto_increment
        primary key,
    Address1        mediumtext charset utf8      null,
    Address2        mediumtext charset utf8      null,
    ZipCode         mediumtext charset utf8      null,
    City            mediumtext charset utf8      null,
    State           mediumtext charset utf8      null,
    Country         mediumtext charset utf8      null,
    WebSiteUrl      mediumtext charset utf8      null,
    Lng             mediumtext charset utf8      null,
    Lat             mediumtext charset utf8      null,
    DisplayOnSite   tinyint unsigned default '0' not null,
    DetailsPage     tinyint unsigned default '0' not null,
    LocationMessage mediumtext charset utf8      null
)
    charset = latin1;

create table SummitGroupEvent
(
    ID          int auto_increment
        primary key,
    CreatedByID int null
)
    charset = latin1;

create index CreatedByID
    on SummitGroupEvent (CreatedByID);

create table SummitGroupEvent_Groups
(
    ID                 int auto_increment
        primary key,
    SummitGroupEventID int default 0 not null,
    GroupID            int default 0 not null
)
    charset = latin1;

create index GroupID
    on SummitGroupEvent_Groups (GroupID);

create index SummitGroupEventID
    on SummitGroupEvent_Groups (SummitGroupEventID);

create table SummitHighlightPic
(
    ID                     int auto_increment
        primary key,
    ClassName              enum ('SummitHighlightPic') charset utf8 default 'SummitHighlightPic' null,
    LastEdited             datetime                                                              null,
    Created                datetime                                                              null,
    Title                  mediumtext charset utf8                                               null,
    `Order`                int                                      default 0                    not null,
    SummitHighlightsPageID int                                                                   null,
    ImageID                int                                                                   null
)
    charset = latin1;

create index ClassName
    on SummitHighlightPic (ClassName);

create index ImageID
    on SummitHighlightPic (ImageID);

create index SummitHighlightsPageID
    on SummitHighlightPic (SummitHighlightsPageID);

create table SummitHighlightsPage
(
    ID                              int auto_increment
        primary key,
    ThankYouText                    mediumtext charset utf8 null,
    NextSummitText                  mediumtext charset utf8 null,
    SuccessTitle                    mediumtext charset utf8 null,
    SuccessAttribution              mediumtext charset utf8 null,
    SuccessAttributionURL           mediumtext charset utf8 null,
    AttendanceQty                   mediumtext charset utf8 null,
    CompaniesRepresentedQty         mediumtext charset utf8 null,
    CountriesRepresentedQty         mediumtext charset utf8 null,
    SessionsQty                     mediumtext charset utf8 null,
    ReleaseAnnouncedTitle           mediumtext charset utf8 null,
    ReleaseAnnouncedDescription     mediumtext charset utf8 null,
    ReleaseAnnouncedButtonTitle     mediumtext charset utf8 null,
    ReleaseAnnouncedButtonLink      mediumtext charset utf8 null,
    CurrentSummitFlickrUrl          mediumtext charset utf8 null,
    StatisticsVideoUrl              mediumtext charset utf8 null,
    StatisticsVideoUrl2             mediumtext charset utf8 null,
    StatisticsVideoUrl3             mediumtext charset utf8 null,
    StatisticsVideoUrl4             mediumtext charset utf8 null,
    ReleaseAnnouncedImageID         int                     null,
    CurrentSummitBackgroundImageID  int                     null,
    NextSummitTinyBackgroundImageID int                     null,
    NextSummitBackgroundImageID     int                     null,
    StatisticsVideoPosterID         int                     null,
    StatisticsVideoID               int                     null
)
    charset = latin1;

create index CurrentSummitBackgroundImageID
    on SummitHighlightsPage (CurrentSummitBackgroundImageID);

create index NextSummitBackgroundImageID
    on SummitHighlightsPage (NextSummitBackgroundImageID);

create index NextSummitTinyBackgroundImageID
    on SummitHighlightsPage (NextSummitTinyBackgroundImageID);

create index ReleaseAnnouncedImageID
    on SummitHighlightsPage (ReleaseAnnouncedImageID);

create index StatisticsVideoID
    on SummitHighlightsPage (StatisticsVideoID);

create index StatisticsVideoPosterID
    on SummitHighlightsPage (StatisticsVideoPosterID);

create table SummitHighlightsPage_Live
(
    ID                              int auto_increment
        primary key,
    ThankYouText                    mediumtext charset utf8 null,
    NextSummitText                  mediumtext charset utf8 null,
    SuccessTitle                    mediumtext charset utf8 null,
    SuccessAttribution              mediumtext charset utf8 null,
    SuccessAttributionURL           mediumtext charset utf8 null,
    AttendanceQty                   mediumtext charset utf8 null,
    CompaniesRepresentedQty         mediumtext charset utf8 null,
    CountriesRepresentedQty         mediumtext charset utf8 null,
    SessionsQty                     mediumtext charset utf8 null,
    ReleaseAnnouncedTitle           mediumtext charset utf8 null,
    ReleaseAnnouncedDescription     mediumtext charset utf8 null,
    ReleaseAnnouncedButtonTitle     mediumtext charset utf8 null,
    ReleaseAnnouncedButtonLink      mediumtext charset utf8 null,
    CurrentSummitFlickrUrl          mediumtext charset utf8 null,
    StatisticsVideoUrl              mediumtext charset utf8 null,
    StatisticsVideoUrl2             mediumtext charset utf8 null,
    StatisticsVideoUrl3             mediumtext charset utf8 null,
    StatisticsVideoUrl4             mediumtext charset utf8 null,
    ReleaseAnnouncedImageID         int                     null,
    CurrentSummitBackgroundImageID  int                     null,
    NextSummitTinyBackgroundImageID int                     null,
    NextSummitBackgroundImageID     int                     null,
    StatisticsVideoPosterID         int                     null,
    StatisticsVideoID               int                     null
)
    charset = latin1;

create index CurrentSummitBackgroundImageID
    on SummitHighlightsPage_Live (CurrentSummitBackgroundImageID);

create index NextSummitBackgroundImageID
    on SummitHighlightsPage_Live (NextSummitBackgroundImageID);

create index NextSummitTinyBackgroundImageID
    on SummitHighlightsPage_Live (NextSummitTinyBackgroundImageID);

create index ReleaseAnnouncedImageID
    on SummitHighlightsPage_Live (ReleaseAnnouncedImageID);

create index StatisticsVideoID
    on SummitHighlightsPage_Live (StatisticsVideoID);

create index StatisticsVideoPosterID
    on SummitHighlightsPage_Live (StatisticsVideoPosterID);

create table SummitHighlightsPage_versions
(
    ID                              int auto_increment
        primary key,
    RecordID                        int default 0           not null,
    Version                         int default 0           not null,
    ThankYouText                    mediumtext charset utf8 null,
    NextSummitText                  mediumtext charset utf8 null,
    SuccessTitle                    mediumtext charset utf8 null,
    SuccessAttribution              mediumtext charset utf8 null,
    SuccessAttributionURL           mediumtext charset utf8 null,
    AttendanceQty                   mediumtext charset utf8 null,
    CompaniesRepresentedQty         mediumtext charset utf8 null,
    CountriesRepresentedQty         mediumtext charset utf8 null,
    SessionsQty                     mediumtext charset utf8 null,
    ReleaseAnnouncedTitle           mediumtext charset utf8 null,
    ReleaseAnnouncedDescription     mediumtext charset utf8 null,
    ReleaseAnnouncedButtonTitle     mediumtext charset utf8 null,
    ReleaseAnnouncedButtonLink      mediumtext charset utf8 null,
    CurrentSummitFlickrUrl          mediumtext charset utf8 null,
    StatisticsVideoUrl              mediumtext charset utf8 null,
    StatisticsVideoUrl2             mediumtext charset utf8 null,
    StatisticsVideoUrl3             mediumtext charset utf8 null,
    StatisticsVideoUrl4             mediumtext charset utf8 null,
    ReleaseAnnouncedImageID         int                     null,
    CurrentSummitBackgroundImageID  int                     null,
    NextSummitTinyBackgroundImageID int                     null,
    NextSummitBackgroundImageID     int                     null,
    StatisticsVideoPosterID         int                     null,
    StatisticsVideoID               int                     null,
    constraint RecordID_Version
        unique (RecordID, Version)
)
    charset = latin1;

create index CurrentSummitBackgroundImageID
    on SummitHighlightsPage_versions (CurrentSummitBackgroundImageID);

create index NextSummitBackgroundImageID
    on SummitHighlightsPage_versions (NextSummitBackgroundImageID);

create index NextSummitTinyBackgroundImageID
    on SummitHighlightsPage_versions (NextSummitTinyBackgroundImageID);

create index RecordID
    on SummitHighlightsPage_versions (RecordID);

create index ReleaseAnnouncedImageID
    on SummitHighlightsPage_versions (ReleaseAnnouncedImageID);

create index StatisticsVideoID
    on SummitHighlightsPage_versions (StatisticsVideoID);

create index StatisticsVideoPosterID
    on SummitHighlightsPage_versions (StatisticsVideoPosterID);

create index Version
    on SummitHighlightsPage_versions (Version);

create table SummitHomePage
(
    ID        int auto_increment
        primary key,
    IntroText varchar(255) charset utf8 null
)
    charset = latin1;

create table SummitHomePage_Live
(
    ID        int auto_increment
        primary key,
    IntroText varchar(255) charset utf8 null
)
    charset = latin1;

create table SummitHomePage_versions
(
    ID        int auto_increment
        primary key,
    RecordID  int default 0             not null,
    Version   int default 0             not null,
    IntroText varchar(255) charset utf8 null,
    constraint RecordID_Version
        unique (RecordID, Version)
)
    charset = latin1;

create index RecordID
    on SummitHomePage_versions (RecordID);

create index Version
    on SummitHomePage_versions (Version);

create table SummitHotel
(
    ID          int auto_increment
        primary key,
    BookingLink mediumtext charset utf8                                      null,
    SoldOut     tinyint unsigned                           default '0'       not null,
    Type        enum ('Primary', 'Alternate') charset utf8 default 'Primary' null
)
    charset = latin1;

create table SummitImage
(
    ID          int auto_increment
        primary key,
    ClassName   enum ('SummitImage') charset utf8 default 'SummitImage' null,
    LastEdited  datetime                                                null,
    Created     datetime                                                null,
    Title       mediumtext charset utf8                                 null,
    Attribution mediumtext charset utf8                                 null,
    Description mediumtext charset utf8                                 null,
    OriginalURL mediumtext charset utf8                                 null,
    ImageID     int                                                     null
)
    charset = latin1;

create index ClassName
    on SummitImage (ClassName);

create index ImageID
    on SummitImage (ImageID);

create table SummitKeynoteHighlight
(
    ID                     int auto_increment
        primary key,
    ClassName              enum ('SummitKeynoteHighlight') charset utf8               default 'SummitKeynoteHighlight' null,
    LastEdited             datetime                                                                                    null,
    Created                datetime                                                                                    null,
    Title                  mediumtext charset utf8                                                                     null,
    Day                    enum ('Day1', 'Day2', 'Day3', 'Day4', 'Day5') charset utf8 default 'Day1'                   null,
    Description            mediumtext charset utf8                                                                     null,
    `Order`                int                                                        default 0                        not null,
    SummitHighlightsPageID int                                                                                         null,
    ImageID                int                                                                                         null,
    ThumbnailID            int                                                                                         null
)
    charset = latin1;

create index ClassName
    on SummitKeynoteHighlight (ClassName);

create index ImageID
    on SummitKeynoteHighlight (ImageID);

create index SummitHighlightsPageID
    on SummitKeynoteHighlight (SummitHighlightsPageID);

create index ThumbnailID
    on SummitKeynoteHighlight (ThumbnailID);

create table SummitLocationBanner
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('SummitLocationBanner', 'ScheduledSummitLocationBanner') charset utf8 default 'SummitLocationBanner' null,
    LastEdited datetime                                                                                                   null,
    Created    datetime                                                                                                   null,
    Title      mediumtext charset utf8                                                                                    null,
    Content    mediumtext charset utf8                                                                                    null,
    Enabled    tinyint unsigned                                                            default '0'                    not null,
    Type       enum ('Primary', 'Secondary') charset utf8                                  default 'Primary'              null,
    LocationID int                                                                                                        null
)
    charset = latin1;

create index ClassName
    on SummitLocationBanner (ClassName);

create index LocationID
    on SummitLocationBanner (LocationID);

create table SummitLocationImage
(
    ID          int auto_increment
        primary key,
    ClassName   enum ('SummitLocationImage', 'SummitLocationMap') charset utf8 default 'SummitLocationImage' null,
    LastEdited  datetime                                                                                     null,
    Created     datetime                                                                                     null,
    Name        varchar(255) charset utf8                                                                    null,
    Description mediumtext charset utf8                                                                      null,
    `Order`     int                                                            default 0                     not null,
    PictureID   int                                                                                          null,
    LocationID  int                                                                                          null
)
    charset = latin1;

create index ClassName
    on SummitLocationImage (ClassName);

create index LocationID
    on SummitLocationImage (LocationID);

create index PictureID
    on SummitLocationImage (PictureID);

create table SummitLocationPage
(
    ID                                    int auto_increment
        primary key,
    VisaInformation                       mediumtext charset utf8   null,
    CityIntro                             mediumtext charset utf8   null,
    LocationsTextHeader                   mediumtext charset utf8   null,
    OtherLocations                        mediumtext charset utf8   null,
    GettingAround                         mediumtext charset utf8   null,
    AboutTheCity                          mediumtext charset utf8   null,
    Locals                                mediumtext charset utf8   null,
    TravelSupport                         mediumtext charset utf8   null,
    AboutTheCityBackgroundImageHero       mediumtext charset utf8   null,
    AboutTheCityBackgroundImageHeroSource mediumtext charset utf8   null,
    HostCityLat                           mediumtext charset utf8   null,
    HostCityLng                           mediumtext charset utf8   null,
    VenueTitleText                        mediumtext charset utf8   null,
    AirportsTitle                         mediumtext charset utf8   null,
    AirportsSubTitle                      mediumtext charset utf8   null,
    CampusGraphic                         mediumtext charset utf8   null,
    VenueBackgroundImageHero              varchar(255) charset utf8 null,
    VenueBackgroundImageHeroSource        varchar(510) charset utf8 null,
    VenueBackgroundImageID                int                       null,
    AboutTheCityBackgroundImageID         int                       null
)
    charset = latin1;

create index AboutTheCityBackgroundImageID
    on SummitLocationPage (AboutTheCityBackgroundImageID);

create index VenueBackgroundImageID
    on SummitLocationPage (VenueBackgroundImageID);

create table SummitLocationPage_Live
(
    ID                                    int auto_increment
        primary key,
    VisaInformation                       mediumtext charset utf8   null,
    CityIntro                             mediumtext charset utf8   null,
    LocationsTextHeader                   mediumtext charset utf8   null,
    OtherLocations                        mediumtext charset utf8   null,
    GettingAround                         mediumtext charset utf8   null,
    AboutTheCity                          mediumtext charset utf8   null,
    Locals                                mediumtext charset utf8   null,
    TravelSupport                         mediumtext charset utf8   null,
    AboutTheCityBackgroundImageHero       mediumtext charset utf8   null,
    AboutTheCityBackgroundImageHeroSource mediumtext charset utf8   null,
    HostCityLat                           mediumtext charset utf8   null,
    HostCityLng                           mediumtext charset utf8   null,
    VenueTitleText                        mediumtext charset utf8   null,
    AirportsTitle                         mediumtext charset utf8   null,
    AirportsSubTitle                      mediumtext charset utf8   null,
    CampusGraphic                         mediumtext charset utf8   null,
    VenueBackgroundImageHero              varchar(255) charset utf8 null,
    VenueBackgroundImageHeroSource        varchar(510) charset utf8 null,
    VenueBackgroundImageID                int                       null,
    AboutTheCityBackgroundImageID         int                       null
)
    charset = latin1;

create index AboutTheCityBackgroundImageID
    on SummitLocationPage_Live (AboutTheCityBackgroundImageID);

create index VenueBackgroundImageID
    on SummitLocationPage_Live (VenueBackgroundImageID);

create table SummitLocationPage_versions
(
    ID                                    int auto_increment
        primary key,
    RecordID                              int default 0             not null,
    Version                               int default 0             not null,
    VisaInformation                       mediumtext charset utf8   null,
    CityIntro                             mediumtext charset utf8   null,
    LocationsTextHeader                   mediumtext charset utf8   null,
    OtherLocations                        mediumtext charset utf8   null,
    GettingAround                         mediumtext charset utf8   null,
    AboutTheCity                          mediumtext charset utf8   null,
    Locals                                mediumtext charset utf8   null,
    TravelSupport                         mediumtext charset utf8   null,
    AboutTheCityBackgroundImageHero       mediumtext charset utf8   null,
    AboutTheCityBackgroundImageHeroSource mediumtext charset utf8   null,
    HostCityLat                           mediumtext charset utf8   null,
    HostCityLng                           mediumtext charset utf8   null,
    VenueTitleText                        mediumtext charset utf8   null,
    AirportsTitle                         mediumtext charset utf8   null,
    AirportsSubTitle                      mediumtext charset utf8   null,
    CampusGraphic                         mediumtext charset utf8   null,
    VenueBackgroundImageHero              varchar(255) charset utf8 null,
    VenueBackgroundImageHeroSource        varchar(510) charset utf8 null,
    VenueBackgroundImageID                int                       null,
    AboutTheCityBackgroundImageID         int                       null,
    constraint RecordID_Version
        unique (RecordID, Version)
)
    charset = latin1;

create index AboutTheCityBackgroundImageID
    on SummitLocationPage_versions (AboutTheCityBackgroundImageID);

create index RecordID
    on SummitLocationPage_versions (RecordID);

create index VenueBackgroundImageID
    on SummitLocationPage_versions (VenueBackgroundImageID);

create index Version
    on SummitLocationPage_versions (Version);

create table SummitMediaFileType
(
    ID                int auto_increment
        primary key,
    Created           datetime                                                   not null,
    LastEdited        datetime                                                   not null,
    ClassName         enum ('SummitMediaFileType') default 'SummitMediaFileType' null,
    Name              varchar(255)                                               not null,
    Description       varchar(255)                                               null,
    IsSystemDefine    tinyint(1)                                                 not null,
    AllowedExtensions varchar(255)                                               not null,
    constraint UNIQ_827E5F3AFE11D138
        unique (Name)
)
    collate = utf8_unicode_ci;

create table SummitMediaUploadType
(
    ID                               int auto_increment
        primary key,
    Created                          datetime                                                                         not null,
    LastEdited                       datetime                                                                         not null,
    ClassName                        enum ('SummitMediaUploadType')                   default 'SummitMediaUploadType' null,
    Name                             varchar(255)                                                                     not null,
    Description                      varchar(255)                                                                     null,
    MaxSize                          int                                              default 1024                    not null,
    IsMandatory                      tinyint(1)                                       default 0                       not null,
    PrivateStorageType               enum ('None', 'DropBox', 'Local', 'Swift')       default 'None'                  null,
    PublicStorageType                enum ('None', 'DropBox', 'S3', 'Swift', 'Local') default 'None'                  null,
    SummitID                         int                                                                              null,
    TypeID                           int                                                                              null,
    UseTemporaryLinksOnPublicStorage tinyint(1)                                       default 0                       not null,
    TemporaryLinksOnPublicStorageTTL int                                              default 0                       not null,
    constraint UNIQ_1362D86390CF7278FE11D138
        unique (SummitID, Name),
    constraint FK_1362D86390CF7278
        foreign key (SummitID) references Summit (ID)
            on delete cascade,
    constraint FK_1362D863A736B16E
        foreign key (TypeID) references SummitMediaFileType (ID)
            on delete cascade
)
    collate = utf8_unicode_ci;

create table PresentationMediaUpload
(
    ID                      int auto_increment
        primary key,
    FileName                varchar(255)         not null,
    SummitMediaUploadTypeID int                  null,
    LegacyPathFormat        tinyint(1) default 1 not null,
    constraint FK_381AC212D70B12DA
        foreign key (SummitMediaUploadTypeID) references SummitMediaUploadType (ID)
            on delete cascade,
    constraint FK_PresentationMaterial
        foreign key (ID) references PresentationMaterial (ID)
            on delete cascade
)
    collate = utf8_unicode_ci;

create index SummitMediaUploadTypeID
    on PresentationMediaUpload (SummitMediaUploadTypeID);

create index IDX_1362D86390CF7278
    on SummitMediaUploadType (SummitID);

create index IDX_1362D863A736B16E
    on SummitMediaUploadType (TypeID);

create index SummitID
    on SummitMediaUploadType (SummitID);

create index TypeID
    on SummitMediaUploadType (TypeID);

create table SummitMetric
(
    ID           int auto_increment
        primary key,
    Created      datetime                                                                                           not null,
    LastEdited   datetime                                                                                           not null,
    ClassName    enum ('SummitMetric', 'SummitEventAttendanceMetric', 'SummitSponsorMetric') default 'SummitMetric' null,
    Type         enum ('GENERAL', 'LOBBY', 'SPONSOR', 'EVENT', 'POSTER', 'POSTERS')          default 'GENERAL'      null,
    Ip           varchar(255)                                                                                       null,
    Origin       varchar(255)                                                                                       null,
    Browser      varchar(255)                                                                                       null,
    IngressDate  datetime                                                                                           null,
    OutgressDate datetime                                                                                           null,
    MemberID     int                                                                                                null,
    SummitID     int                                                                                                null,
    Location     longtext                                                                                           null,
    constraint FK_D04B9CCF522B9974
        foreign key (MemberID) references Member (ID)
            on delete cascade,
    constraint FK_D04B9CCF90CF7278
        foreign key (SummitID) references Summit (ID)
            on delete cascade
)
    collate = utf8_unicode_ci;

create table SummitEventAttendanceMetric
(
    ID            int                                                                        not null
        primary key,
    ClassName     enum ('SummitEventAttendanceMetric') default 'SummitEventAttendanceMetric' null,
    SummitEventID int                                                                        null,
    constraint FK_967BCC3722CF6AF5
        foreign key (SummitEventID) references SummitEvent (ID)
            on delete cascade,
    constraint FK_SummitEventAttendanceMetric_SummitMetric
        foreign key (ID) references SummitMetric (ID)
            on delete cascade
)
    collate = utf8_unicode_ci;

create index SummitEventID
    on SummitEventAttendanceMetric (SummitEventID);

create index MemberID
    on SummitMetric (MemberID);

create index SummitID
    on SummitMetric (SummitID);

create table SummitNetworkingPhoto
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('SummitNetworkingPhoto') charset utf8 default 'SummitNetworkingPhoto' null,
    LastEdited datetime                                                                    null,
    Created    datetime                                                                    null,
    `Order`    int                                         default 0                       not null,
    ImageID    int                                                                         null,
    OwnerID    int                                                                         null
)
    charset = latin1;

create index ClassName
    on SummitNetworkingPhoto (ClassName);

create index ImageID
    on SummitNetworkingPhoto (ImageID);

create index OwnerID
    on SummitNetworkingPhoto (OwnerID);

create table SummitOrder
(
    ID                           int auto_increment
        primary key,
    ClassName                    enum ('SummitOrder') charset utf8                                                                        default 'SummitOrder' null,
    LastEdited                   datetime                                                                                                                       null,
    Created                      datetime                                                                                                                       null,
    Number                       varchar(255) charset utf8                                                                                                      null,
    ExternalId                   varchar(255) charset utf8                                                                                                      null,
    PaymentMethod                enum ('Online', 'Offline') charset utf8                                                                  default 'Offline'     null,
    Status                       enum ('Reserved', 'Cancelled', 'RefundRequested', 'Refunded', 'Confirmed', 'Paid', 'Error') charset utf8 default 'Reserved'    null,
    OwnerFirstName               varchar(255) charset utf8                                                                                                      null,
    OwnerSurname                 varchar(255) charset utf8                                                                                                      null,
    OwnerEmail                   varchar(100) charset utf8                                                                                                      null,
    OwnerCompany                 varchar(255) charset utf8                                                                                                      null,
    BillingAddress1              varchar(100) charset utf8                                                                                                      null,
    BillingAddress2              varchar(100) charset utf8                                                                                                      null,
    BillingAddressZipCode        varchar(50) charset utf8                                                                                                       null,
    BillingAddressCity           varchar(50) charset utf8                                                                                                       null,
    BillingAddressState          varchar(50) charset utf8                                                                                                       null,
    BillingAddressCountryISOCode varchar(3) charset utf8                                                                                                        null,
    ApprovedPaymentDate          datetime                                                                                                                       null,
    LastError                    varchar(255) charset utf8                                                                                                      null,
    PaymentGatewayCartId         varchar(512) charset utf8                                                                                                      null,
    PaymentGatewayClientToken    mediumtext charset utf8                                                                                                        null,
    QRCode                       varchar(255) charset utf8                                                                                                      null,
    Hash                         varchar(255) charset utf8                                                                                                      null,
    HashCreationDate             datetime                                                                                                                       null,
    RefundedAmount               decimal(9, 2)                                                                                            default 0.00          not null,
    SummitID                     int                                                                                                                            null,
    OwnerID                      int                                                                                                                            null,
    OwnerCompanyID               int                                                                                                                            null,
    LastReminderEmailSentDate    datetime                                                                                                                       null,
    constraint FK_SummitOrder_Company
        foreign key (OwnerCompanyID) references Company (ID)
            on delete set null
)
    charset = latin1;

create index ClassName
    on SummitOrder (ClassName);

create index CompanyID
    on SummitOrder (OwnerCompanyID);

create index OwnerID
    on SummitOrder (OwnerID);

create index SummitID
    on SummitOrder (SummitID);

create table SummitOrderExtraQuestionAnswer
(
    ID               int auto_increment
        primary key,
    OrderID          int null,
    SummitAttendeeID int null,
    constraint `FK_ SummitOrderExtraQuestionAnswer_Attendee`
        foreign key (SummitAttendeeID) references SummitAttendee (ID)
            on delete cascade,
    constraint FK_SummitOrderExtraQuestionAnswer_Order
        foreign key (OrderID) references SummitOrder (ID)
            on delete cascade,
    constraint JT_SummitOrderExtraQuestionAnswer_ExtraQuestionAnswer
        foreign key (ID) references ExtraQuestionAnswer (ID)
            on delete cascade
)
    charset = latin1;

create index OrderID
    on SummitOrderExtraQuestionAnswer (OrderID);

create index SummitAttendeeID
    on SummitOrderExtraQuestionAnswer (SummitAttendeeID);

create table SummitOrderExtraQuestionType
(
    ID         int auto_increment
        primary key,
    `Usage`    enum ('Order', 'Ticket', 'Both') charset utf8 default 'Order' null,
    Printable  tinyint unsigned                              default '0'     not null,
    SummitID   int                                                           null,
    ExternalId longtext                                                      null,
    constraint JT_SummitOrderExtraQuestionType_ExtraQuestionType
        foreign key (ID) references ExtraQuestionType (ID)
            on delete cascade
)
    charset = latin1;

create index SummitID
    on SummitOrderExtraQuestionType (SummitID);

create table SummitOverviewPage
(
    ID                        int auto_increment
        primary key,
    OverviewIntro             mediumtext charset utf8 null,
    GrowthBoxTextTop          mediumtext charset utf8 null,
    GrowthBoxTextBottom       mediumtext charset utf8 null,
    RecapTitle                mediumtext charset utf8 null,
    VideoRecapCaption1        mediumtext charset utf8 null,
    VideoRecapYouTubeID1      mediumtext charset utf8 null,
    VideoRecapCaption2        mediumtext charset utf8 null,
    VideoRecapYouTubeID2      mediumtext charset utf8 null,
    ScheduleTitle             mediumtext charset utf8 null,
    ScheduleText              mediumtext charset utf8 null,
    ScheduleUrl               mediumtext charset utf8 null,
    ScheduleBtnText           mediumtext charset utf8 null,
    NetworkingContent         mediumtext charset utf8 null,
    TwoMainEventsTitle        mediumtext charset utf8 null,
    EventOneTitle             mediumtext charset utf8 null,
    EventOneSubTitle          mediumtext charset utf8 null,
    EventOneContent           mediumtext charset utf8 null,
    EventTwoTitle             mediumtext charset utf8 null,
    EventTwoSubTitle          mediumtext charset utf8 null,
    EventTwoContent           mediumtext charset utf8 null,
    Atendees1Label            mediumtext charset utf8 null,
    Atendees2Label            mediumtext charset utf8 null,
    Atendees3Label            mediumtext charset utf8 null,
    Atendees4Label            mediumtext charset utf8 null,
    TimelineCaption           mediumtext charset utf8 null,
    GrowthBoxBackgroundID     int                     null,
    GrowthBoxChartLegendID    int                     null,
    GrowthBoxChartLegendPngID int                     null,
    GrowthBoxChartID          int                     null,
    GrowthBoxChartPngID       int                     null,
    EventOneLogoID            int                     null,
    EventOneLogoPngID         int                     null,
    EventTwoLogoID            int                     null,
    EventTwoLogoPngID         int                     null,
    Atendees1ChartID          int                     null,
    Atendees1ChartPngID       int                     null,
    Atendees2ChartID          int                     null,
    Atendees2ChartPngID       int                     null,
    Atendees3ChartID          int                     null,
    Atendees3ChartPngID       int                     null,
    Atendees4ChartID          int                     null,
    Atendees4ChartPngID       int                     null,
    AtendeesChartRefID        int                     null,
    AtendeesChartRefPngID     int                     null,
    TimelineImageID           int                     null,
    TimelineImagePngID        int                     null
)
    charset = latin1;

create index Atendees1ChartID
    on SummitOverviewPage (Atendees1ChartID);

create index Atendees1ChartPngID
    on SummitOverviewPage (Atendees1ChartPngID);

create index Atendees2ChartID
    on SummitOverviewPage (Atendees2ChartID);

create index Atendees2ChartPngID
    on SummitOverviewPage (Atendees2ChartPngID);

create index Atendees3ChartID
    on SummitOverviewPage (Atendees3ChartID);

create index Atendees3ChartPngID
    on SummitOverviewPage (Atendees3ChartPngID);

create index Atendees4ChartID
    on SummitOverviewPage (Atendees4ChartID);

create index Atendees4ChartPngID
    on SummitOverviewPage (Atendees4ChartPngID);

create index AtendeesChartRefID
    on SummitOverviewPage (AtendeesChartRefID);

create index AtendeesChartRefPngID
    on SummitOverviewPage (AtendeesChartRefPngID);

create index EventOneLogoID
    on SummitOverviewPage (EventOneLogoID);

create index EventOneLogoPngID
    on SummitOverviewPage (EventOneLogoPngID);

create index EventTwoLogoID
    on SummitOverviewPage (EventTwoLogoID);

create index EventTwoLogoPngID
    on SummitOverviewPage (EventTwoLogoPngID);

create index GrowthBoxBackgroundID
    on SummitOverviewPage (GrowthBoxBackgroundID);

create index GrowthBoxChartID
    on SummitOverviewPage (GrowthBoxChartID);

create index GrowthBoxChartLegendID
    on SummitOverviewPage (GrowthBoxChartLegendID);

create index GrowthBoxChartLegendPngID
    on SummitOverviewPage (GrowthBoxChartLegendPngID);

create index GrowthBoxChartPngID
    on SummitOverviewPage (GrowthBoxChartPngID);

create index TimelineImageID
    on SummitOverviewPage (TimelineImageID);

create index TimelineImagePngID
    on SummitOverviewPage (TimelineImagePngID);

create table SummitOverviewPageHelpMenuItem
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('SummitOverviewPageHelpMenuItem') charset utf8                                                                                                                                                                                                              default 'SummitOverviewPageHelpMenuItem' null,
    LastEdited datetime                                                                                                                                                                                                                                                                                                   null,
    Created    datetime                                                                                                                                                                                                                                                                                                   null,
    Label      mediumtext charset utf8                                                                                                                                                                                                                                                                                    null,
    Url        mediumtext charset utf8                                                                                                                                                                                                                                                                                    null,
    FAIcon     enum ('fa-h-square', 'fa-comment', 'fa-tag', 'fa-question', 'fa-users', 'fa-mobile', 'none', 'fa-map-signs', 'fa-map', 'fa-calendar', 'fa-bed', 'fa-beer', 'fa-cab', 'fa-compass', 'fa-cutlery', 'fa-location-arrow', 'fa-venus', 'fa-youtube-play') charset utf8 default 'none'                           null,
    `Order`    int                                                                                                                                                                                                                                                               default 0                                not null,
    OwnerID    int                                                                                                                                                                                                                                                                                                        null
)
    charset = latin1;

create index ClassName
    on SummitOverviewPageHelpMenuItem (ClassName);

create index OwnerID
    on SummitOverviewPageHelpMenuItem (OwnerID);

create table SummitOverviewPage_Live
(
    ID                        int auto_increment
        primary key,
    OverviewIntro             mediumtext charset utf8 null,
    GrowthBoxTextTop          mediumtext charset utf8 null,
    GrowthBoxTextBottom       mediumtext charset utf8 null,
    RecapTitle                mediumtext charset utf8 null,
    VideoRecapCaption1        mediumtext charset utf8 null,
    VideoRecapYouTubeID1      mediumtext charset utf8 null,
    VideoRecapCaption2        mediumtext charset utf8 null,
    VideoRecapYouTubeID2      mediumtext charset utf8 null,
    ScheduleTitle             mediumtext charset utf8 null,
    ScheduleText              mediumtext charset utf8 null,
    ScheduleUrl               mediumtext charset utf8 null,
    ScheduleBtnText           mediumtext charset utf8 null,
    NetworkingContent         mediumtext charset utf8 null,
    TwoMainEventsTitle        mediumtext charset utf8 null,
    EventOneTitle             mediumtext charset utf8 null,
    EventOneSubTitle          mediumtext charset utf8 null,
    EventOneContent           mediumtext charset utf8 null,
    EventTwoTitle             mediumtext charset utf8 null,
    EventTwoSubTitle          mediumtext charset utf8 null,
    EventTwoContent           mediumtext charset utf8 null,
    Atendees1Label            mediumtext charset utf8 null,
    Atendees2Label            mediumtext charset utf8 null,
    Atendees3Label            mediumtext charset utf8 null,
    Atendees4Label            mediumtext charset utf8 null,
    TimelineCaption           mediumtext charset utf8 null,
    GrowthBoxBackgroundID     int                     null,
    GrowthBoxChartLegendID    int                     null,
    GrowthBoxChartLegendPngID int                     null,
    GrowthBoxChartID          int                     null,
    GrowthBoxChartPngID       int                     null,
    EventOneLogoID            int                     null,
    EventOneLogoPngID         int                     null,
    EventTwoLogoID            int                     null,
    EventTwoLogoPngID         int                     null,
    Atendees1ChartID          int                     null,
    Atendees1ChartPngID       int                     null,
    Atendees2ChartID          int                     null,
    Atendees2ChartPngID       int                     null,
    Atendees3ChartID          int                     null,
    Atendees3ChartPngID       int                     null,
    Atendees4ChartID          int                     null,
    Atendees4ChartPngID       int                     null,
    AtendeesChartRefID        int                     null,
    AtendeesChartRefPngID     int                     null,
    TimelineImageID           int                     null,
    TimelineImagePngID        int                     null
)
    charset = latin1;

create index Atendees1ChartID
    on SummitOverviewPage_Live (Atendees1ChartID);

create index Atendees1ChartPngID
    on SummitOverviewPage_Live (Atendees1ChartPngID);

create index Atendees2ChartID
    on SummitOverviewPage_Live (Atendees2ChartID);

create index Atendees2ChartPngID
    on SummitOverviewPage_Live (Atendees2ChartPngID);

create index Atendees3ChartID
    on SummitOverviewPage_Live (Atendees3ChartID);

create index Atendees3ChartPngID
    on SummitOverviewPage_Live (Atendees3ChartPngID);

create index Atendees4ChartID
    on SummitOverviewPage_Live (Atendees4ChartID);

create index Atendees4ChartPngID
    on SummitOverviewPage_Live (Atendees4ChartPngID);

create index AtendeesChartRefID
    on SummitOverviewPage_Live (AtendeesChartRefID);

create index AtendeesChartRefPngID
    on SummitOverviewPage_Live (AtendeesChartRefPngID);

create index EventOneLogoID
    on SummitOverviewPage_Live (EventOneLogoID);

create index EventOneLogoPngID
    on SummitOverviewPage_Live (EventOneLogoPngID);

create index EventTwoLogoID
    on SummitOverviewPage_Live (EventTwoLogoID);

create index EventTwoLogoPngID
    on SummitOverviewPage_Live (EventTwoLogoPngID);

create index GrowthBoxBackgroundID
    on SummitOverviewPage_Live (GrowthBoxBackgroundID);

create index GrowthBoxChartID
    on SummitOverviewPage_Live (GrowthBoxChartID);

create index GrowthBoxChartLegendID
    on SummitOverviewPage_Live (GrowthBoxChartLegendID);

create index GrowthBoxChartLegendPngID
    on SummitOverviewPage_Live (GrowthBoxChartLegendPngID);

create index GrowthBoxChartPngID
    on SummitOverviewPage_Live (GrowthBoxChartPngID);

create index TimelineImageID
    on SummitOverviewPage_Live (TimelineImageID);

create index TimelineImagePngID
    on SummitOverviewPage_Live (TimelineImagePngID);

create table SummitOverviewPage_versions
(
    ID                        int auto_increment
        primary key,
    RecordID                  int default 0           not null,
    Version                   int default 0           not null,
    OverviewIntro             mediumtext charset utf8 null,
    GrowthBoxTextTop          mediumtext charset utf8 null,
    GrowthBoxTextBottom       mediumtext charset utf8 null,
    RecapTitle                mediumtext charset utf8 null,
    VideoRecapCaption1        mediumtext charset utf8 null,
    VideoRecapYouTubeID1      mediumtext charset utf8 null,
    VideoRecapCaption2        mediumtext charset utf8 null,
    VideoRecapYouTubeID2      mediumtext charset utf8 null,
    ScheduleTitle             mediumtext charset utf8 null,
    ScheduleText              mediumtext charset utf8 null,
    ScheduleUrl               mediumtext charset utf8 null,
    ScheduleBtnText           mediumtext charset utf8 null,
    NetworkingContent         mediumtext charset utf8 null,
    TwoMainEventsTitle        mediumtext charset utf8 null,
    EventOneTitle             mediumtext charset utf8 null,
    EventOneSubTitle          mediumtext charset utf8 null,
    EventOneContent           mediumtext charset utf8 null,
    EventTwoTitle             mediumtext charset utf8 null,
    EventTwoSubTitle          mediumtext charset utf8 null,
    EventTwoContent           mediumtext charset utf8 null,
    Atendees1Label            mediumtext charset utf8 null,
    Atendees2Label            mediumtext charset utf8 null,
    Atendees3Label            mediumtext charset utf8 null,
    Atendees4Label            mediumtext charset utf8 null,
    TimelineCaption           mediumtext charset utf8 null,
    GrowthBoxBackgroundID     int                     null,
    GrowthBoxChartLegendID    int                     null,
    GrowthBoxChartLegendPngID int                     null,
    GrowthBoxChartID          int                     null,
    GrowthBoxChartPngID       int                     null,
    EventOneLogoID            int                     null,
    EventOneLogoPngID         int                     null,
    EventTwoLogoID            int                     null,
    EventTwoLogoPngID         int                     null,
    Atendees1ChartID          int                     null,
    Atendees1ChartPngID       int                     null,
    Atendees2ChartID          int                     null,
    Atendees2ChartPngID       int                     null,
    Atendees3ChartID          int                     null,
    Atendees3ChartPngID       int                     null,
    Atendees4ChartID          int                     null,
    Atendees4ChartPngID       int                     null,
    AtendeesChartRefID        int                     null,
    AtendeesChartRefPngID     int                     null,
    TimelineImageID           int                     null,
    TimelineImagePngID        int                     null,
    constraint RecordID_Version
        unique (RecordID, Version)
)
    charset = latin1;

create index Atendees1ChartID
    on SummitOverviewPage_versions (Atendees1ChartID);

create index Atendees1ChartPngID
    on SummitOverviewPage_versions (Atendees1ChartPngID);

create index Atendees2ChartID
    on SummitOverviewPage_versions (Atendees2ChartID);

create index Atendees2ChartPngID
    on SummitOverviewPage_versions (Atendees2ChartPngID);

create index Atendees3ChartID
    on SummitOverviewPage_versions (Atendees3ChartID);

create index Atendees3ChartPngID
    on SummitOverviewPage_versions (Atendees3ChartPngID);

create index Atendees4ChartID
    on SummitOverviewPage_versions (Atendees4ChartID);

create index Atendees4ChartPngID
    on SummitOverviewPage_versions (Atendees4ChartPngID);

create index AtendeesChartRefID
    on SummitOverviewPage_versions (AtendeesChartRefID);

create index AtendeesChartRefPngID
    on SummitOverviewPage_versions (AtendeesChartRefPngID);

create index EventOneLogoID
    on SummitOverviewPage_versions (EventOneLogoID);

create index EventOneLogoPngID
    on SummitOverviewPage_versions (EventOneLogoPngID);

create index EventTwoLogoID
    on SummitOverviewPage_versions (EventTwoLogoID);

create index EventTwoLogoPngID
    on SummitOverviewPage_versions (EventTwoLogoPngID);

create index GrowthBoxBackgroundID
    on SummitOverviewPage_versions (GrowthBoxBackgroundID);

create index GrowthBoxChartID
    on SummitOverviewPage_versions (GrowthBoxChartID);

create index GrowthBoxChartLegendID
    on SummitOverviewPage_versions (GrowthBoxChartLegendID);

create index GrowthBoxChartLegendPngID
    on SummitOverviewPage_versions (GrowthBoxChartLegendPngID);

create index GrowthBoxChartPngID
    on SummitOverviewPage_versions (GrowthBoxChartPngID);

create index RecordID
    on SummitOverviewPage_versions (RecordID);

create index TimelineImageID
    on SummitOverviewPage_versions (TimelineImageID);

create index TimelineImagePngID
    on SummitOverviewPage_versions (TimelineImagePngID);

create index Version
    on SummitOverviewPage_versions (Version);

create table SummitPackage
(
    ID                 int auto_increment
        primary key,
    ClassName          enum ('SummitPackage') charset utf8 default 'SummitPackage' null,
    LastEdited         datetime                                                    null,
    Created            datetime                                                    null,
    Title              mediumtext charset utf8                                     null,
    SubTitle           mediumtext charset utf8                                     null,
    Cost               decimal(9, 2)                       default 0.00            not null,
    MaxAvailable       int                                 default 0               not null,
    CurrentlyAvailable int                                 default 0               not null,
    `Order`            int                                 default 0               not null,
    ShowQuantity       tinyint unsigned                    default '0'             not null,
    SummitID           int                                                         null
)
    charset = latin1;

create index ClassName
    on SummitPackage (ClassName);

create index SummitID
    on SummitPackage (SummitID);

create table SummitPackagePurchaseOrder
(
    ID                       int auto_increment
        primary key,
    ClassName                enum ('SummitPackagePurchaseOrder') charset utf8 default 'SummitPackagePurchaseOrder' null,
    LastEdited               datetime                                                                              null,
    Created                  datetime                                                                              null,
    FirstName                varchar(50) charset utf8                                                              null,
    Surname                  varchar(50) charset utf8                                                              null,
    Email                    varchar(254) charset utf8                                                             null,
    Organization             varchar(50) charset utf8                                                              null,
    Approved                 tinyint unsigned                                 default '0'                          not null,
    ApprovedDate             datetime                                                                              null,
    Rejected                 tinyint unsigned                                 default '0'                          not null,
    RejectedDate             datetime                                                                              null,
    RegisteredOrganizationID int                                                                                   null,
    ApprovedByID             int                                                                                   null,
    RejectedByID             int                                                                                   null,
    PackageID                int                                                                                   null
)
    charset = latin1;

create index ApprovedByID
    on SummitPackagePurchaseOrder (ApprovedByID);

create index ClassName
    on SummitPackagePurchaseOrder (ClassName);

create index PackageID
    on SummitPackagePurchaseOrder (PackageID);

create index RegisteredOrganizationID
    on SummitPackagePurchaseOrder (RegisteredOrganizationID);

create index RejectedByID
    on SummitPackagePurchaseOrder (RejectedByID);

create table SummitPackage_DiscountPackages
(
    ID              int auto_increment
        primary key,
    SummitPackageID int           default 0      not null,
    ChildID         int           default 0      not null,
    Discount        decimal(5, 4) default 0.0000 not null
)
    charset = latin1;

create index ChildID
    on SummitPackage_DiscountPackages (ChildID);

create index SummitPackageID
    on SummitPackage_DiscountPackages (SummitPackageID);

create table SummitPage
(
    ID                   int auto_increment
        primary key,
    GAConversionId       mediumtext charset utf8      null,
    GAConversionLanguage mediumtext charset utf8      null,
    GAConversionFormat   mediumtext charset utf8      null,
    GAConversionColor    mediumtext charset utf8      null,
    GAConversionLabel    mediumtext charset utf8      null,
    GAConversionValue    int              default 0   not null,
    GARemarketingOnly    tinyint unsigned default '0' not null,
    FBPixelId            mediumtext charset utf8      null,
    TwitterPixelId       mediumtext charset utf8      null,
    HeroCSSClass         mediumtext charset utf8      null,
    HeaderText           mediumtext charset utf8      null,
    HeaderMessage        mediumtext charset utf8      null,
    FooterLinksLeft      mediumtext charset utf8      null,
    FooterLinksRight     mediumtext charset utf8      null,
    SummitImageID        int                          null,
    SummitID             int                          null
)
    charset = latin1;

create index SummitID
    on SummitPage (SummitID);

create index SummitImageID
    on SummitPage (SummitImageID);

create table SummitPage_Live
(
    ID                   int auto_increment
        primary key,
    GAConversionId       mediumtext charset utf8      null,
    GAConversionLanguage mediumtext charset utf8      null,
    GAConversionFormat   mediumtext charset utf8      null,
    GAConversionColor    mediumtext charset utf8      null,
    GAConversionLabel    mediumtext charset utf8      null,
    GAConversionValue    int              default 0   not null,
    GARemarketingOnly    tinyint unsigned default '0' not null,
    FBPixelId            mediumtext charset utf8      null,
    TwitterPixelId       mediumtext charset utf8      null,
    HeroCSSClass         mediumtext charset utf8      null,
    HeaderText           mediumtext charset utf8      null,
    HeaderMessage        mediumtext charset utf8      null,
    FooterLinksLeft      mediumtext charset utf8      null,
    FooterLinksRight     mediumtext charset utf8      null,
    SummitImageID        int                          null,
    SummitID             int                          null
)
    charset = latin1;

create index SummitID
    on SummitPage_Live (SummitID);

create index SummitImageID
    on SummitPage_Live (SummitImageID);

create table SummitPage_versions
(
    ID                   int auto_increment
        primary key,
    RecordID             int              default 0   not null,
    Version              int              default 0   not null,
    GAConversionId       mediumtext charset utf8      null,
    GAConversionLanguage mediumtext charset utf8      null,
    GAConversionFormat   mediumtext charset utf8      null,
    GAConversionColor    mediumtext charset utf8      null,
    GAConversionLabel    mediumtext charset utf8      null,
    GAConversionValue    int              default 0   not null,
    GARemarketingOnly    tinyint unsigned default '0' not null,
    FBPixelId            mediumtext charset utf8      null,
    TwitterPixelId       mediumtext charset utf8      null,
    HeroCSSClass         mediumtext charset utf8      null,
    HeaderText           mediumtext charset utf8      null,
    HeaderMessage        mediumtext charset utf8      null,
    FooterLinksLeft      mediumtext charset utf8      null,
    FooterLinksRight     mediumtext charset utf8      null,
    SummitImageID        int                          null,
    SummitID             int                          null,
    constraint RecordID_Version
        unique (RecordID, Version)
)
    charset = latin1;

create index RecordID
    on SummitPage_versions (RecordID);

create index SummitID
    on SummitPage_versions (SummitID);

create index SummitImageID
    on SummitPage_versions (SummitImageID);

create index Version
    on SummitPage_versions (Version);

create table SummitPieDataItem
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('SummitPieDataItem', 'SummitPieDataItemRegion', 'SummitPieDataItemRole') charset utf8 default 'SummitPieDataItem' null,
    LastEdited datetime                                                                                                                null,
    Created    datetime                                                                                                                null,
    Color      mediumtext charset utf8                                                                                                 null,
    Value      mediumtext charset utf8                                                                                                 null,
    Label      mediumtext charset utf8                                                                                                 null,
    `Order`    int                                                                                         default 0                   not null,
    OwnerID    int                                                                                                                     null
)
    charset = latin1;

create index ClassName
    on SummitPieDataItem (ClassName);

create index OwnerID
    on SummitPieDataItem (OwnerID);

create table SummitPresentationComment
(
    ID             int auto_increment
        primary key,
    ClassName      enum ('SummitPresentationComment') charset utf8 default 'SummitPresentationComment' null,
    LastEdited     datetime                                                                            null,
    Created        datetime                                                                            null,
    Body           mediumtext charset utf8                                                             null,
    IsActivity     tinyint unsigned                                default '0'                         not null,
    IsPublic       tinyint unsigned                                default '0'                         not null,
    PresentationID int                                                                                 null,
    CommenterID    int                                                                                 null
)
    charset = latin1;

create index ClassName
    on SummitPresentationComment (ClassName);

create index CommenterID
    on SummitPresentationComment (CommenterID);

create index PresentationID
    on SummitPresentationComment (PresentationID);

create table SummitPushNotification
(
    ID       int auto_increment
        primary key,
    Channel  enum ('EVERYONE', 'SPEAKERS', 'ATTENDEES', 'MEMBERS', 'SUMMIT', 'EVENT', 'GROUP') charset utf8 default 'EVERYONE' null,
    SummitID int                                                                                                               null,
    EventID  int                                                                                                               null,
    GroupID  int                                                                                                               null
)
    charset = latin1;

create index EventID
    on SummitPushNotification (EventID);

create index GroupID
    on SummitPushNotification (GroupID);

create index SummitID
    on SummitPushNotification (SummitID);

create table SummitPushNotification_Recipients
(
    ID                       int auto_increment
        primary key,
    SummitPushNotificationID int default 0 not null,
    MemberID                 int default 0 not null
)
    charset = latin1;

create index MemberID
    on SummitPushNotification_Recipients (MemberID);

create index SummitPushNotificationID
    on SummitPushNotification_Recipients (SummitPushNotificationID);

create table SummitQuestion
(
    ID                    int auto_increment
        primary key,
    ClassName             enum ('SummitQuestion') charset utf8 default 'SummitQuestion' null,
    LastEdited            datetime                                                      null,
    Created               datetime                                                      null,
    `Order`               int                                  default 0                not null,
    Question              mediumtext charset utf8                                       null,
    Answer                mediumtext charset utf8                                       null,
    ExtendedAnswer        mediumtext charset utf8                                       null,
    SummitQuestionsPageID int                                                           null,
    CategoryID            int                                                           null
)
    charset = latin1;

create index CategoryID
    on SummitQuestion (CategoryID);

create index ClassName
    on SummitQuestion (ClassName);

create index SummitQuestionsPageID
    on SummitQuestion (SummitQuestionsPageID);

create table SummitQuestionCategory
(
    ID                    int auto_increment
        primary key,
    ClassName             enum ('SummitQuestionCategory') charset utf8 default 'SummitQuestionCategory' null,
    LastEdited            datetime                                                                      null,
    Created               datetime                                                                      null,
    `Order`               int                                          default 0                        not null,
    Name                  mediumtext charset utf8                                                       null,
    SummitQuestionsPageID int                                                                           null
)
    charset = latin1;

create index ClassName
    on SummitQuestionCategory (ClassName);

create index SummitQuestionsPageID
    on SummitQuestionCategory (SummitQuestionsPageID);

create table SummitRefundPolicyType
(
    ID                          int auto_increment
        primary key,
    ClassName                   enum ('SummitRefundPolicyType') charset utf8 default 'SummitRefundPolicyType' null,
    LastEdited                  datetime                                                                      null,
    Created                     datetime                                                                      null,
    Name                        varchar(255) charset utf8                                                     null,
    UntilXDaysBeforeEventStarts int                                          default 0                        not null,
    RefundRate                  decimal(9, 2)                                default 0.00                     not null,
    SummitID                    int                                                                           null
)
    charset = latin1;

create index ClassName
    on SummitRefundPolicyType (ClassName);

create index SummitID
    on SummitRefundPolicyType (SummitID);

create table SummitRefundRequest
(
    ID                   int auto_increment
        primary key,
    Created              datetime                                                                                                     not null,
    LastEdited           datetime                                                                                                     not null,
    ClassName            enum ('SummitRefundRequest', 'SummitAttendeeTicketRefundRequest') charset utf8 default 'SummitRefundRequest' null,
    RefundedAmount       decimal(9, 2)                                                                  default 0.00                  not null,
    Notes                longtext                                                                                                     null,
    ActionDate           datetime                                                                                                     null,
    Status               enum ('Requested', 'Approved', 'Rejected') charset utf8                        default 'Requested'           null,
    PaymentGatewayResult longtext                                                                                                     null,
    RequestedByID        int                                                                                                          null,
    ActionByID           int                                                                                                          null,
    constraint FK_44392ED424BFE9DA
        foreign key (ActionByID) references Member (ID)
            on delete cascade,
    constraint FK_44392ED4DB2F4727
        foreign key (RequestedByID) references Member (ID)
            on delete cascade
)
    collate = utf8_unicode_ci;

create index ActionByID
    on SummitRefundRequest (ActionByID);

create index RequestedByID
    on SummitRefundRequest (RequestedByID);

create table SummitRegistrationDiscountCode_AllowedTicketTypes
(
    ID                               int auto_increment
        primary key,
    SummitRegistrationDiscountCodeID int           default 0    not null,
    SummitTicketTypeID               int           default 0    not null,
    DiscountRate                     decimal(9, 2) default 0.00 not null,
    DiscountAmount                   decimal(9, 2) default 0.00 not null
)
    charset = latin1;

create index SummitRegistrationDiscountCodeID
    on SummitRegistrationDiscountCode_AllowedTicketTypes (SummitRegistrationDiscountCodeID);

create index SummitTicketTypeID
    on SummitRegistrationDiscountCode_AllowedTicketTypes (SummitTicketTypeID);

create table SummitRegistrationInvitation
(
    ID              int auto_increment
        primary key,
    Created         datetime                                                                     not null,
    LastEdited      datetime                                                                     not null,
    ClassName       enum ('SummitRegistrationInvitation') default 'SummitRegistrationInvitation' null,
    Hash            varchar(255)                                                                 null,
    AcceptedDate    datetime                                                                     null,
    Email           varchar(255)                                                                 not null,
    FirstName       varchar(100)                                                                 not null,
    LastName        varchar(100)                                                                 not null,
    SetPasswordLink varchar(255)                                                                 null,
    MemberID        int                                                                          null,
    SummitID        int                                                                          null,
    SummitOrderID   int                                                                          null,
    constraint Hash
        unique (Hash),
    constraint UNIQ_ACF9E7B82653537090CF7278
        unique (Email, SummitID),
    constraint FK_ACF9E7B8522B9974
        foreign key (MemberID) references Member (ID)
            on delete cascade,
    constraint FK_ACF9E7B890CF7278
        foreign key (SummitID) references Summit (ID)
            on delete cascade,
    constraint FK_ACF9E7B8F3C2A5AE
        foreign key (SummitOrderID) references SummitOrder (ID)
            on delete cascade
)
    collate = utf8_unicode_ci;

create index MemberID
    on SummitRegistrationInvitation (MemberID);

create index SummitID
    on SummitRegistrationInvitation (SummitID);

create index SummitOrderID
    on SummitRegistrationInvitation (SummitOrderID);

create table SummitRegistrationInvitation_SummitTicketTypes
(
    ID                             int auto_increment
        primary key,
    SummitTicketTypeID             int null,
    SummitRegistrationInvitationID int null,
    constraint UNIQ_76A2AA29398EA10C3A19CA8
        unique (SummitTicketTypeID, SummitRegistrationInvitationID)
)
    collate = utf8_unicode_ci;

create index SummitRegistrationInvitationID
    on SummitRegistrationInvitation_SummitTicketTypes (SummitRegistrationInvitationID);

create index SummitTicketTypeID
    on SummitRegistrationInvitation_SummitTicketTypes (SummitTicketTypeID);

create table SummitRegistrationPromoCode
(
    ID                int auto_increment
        primary key,
    ClassName         enum ('SummitRegistrationPromoCode', 'MemberSummitRegistrationPromoCode', 'SponsorSummitRegistrationPromoCode', 'SpeakerSummitRegistrationPromoCode', 'SummitRegistrationDiscountCode', 'MemberSummitRegistrationDiscountCode', 'SponsorSummitRegistrationDiscountCode', 'SpeakerSummitRegistrationDiscountCode') charset utf8 default 'SummitRegistrationPromoCode' null,
    LastEdited        datetime                                                                                                                                                                                                                                                                                                                                                             null,
    Created           datetime                                                                                                                                                                                                                                                                                                                                                             null,
    Code              varchar(255) charset utf8                                                                                                                                                                                                                                                                                                                                            null,
    EmailSent         tinyint unsigned                                                                                                                                                                                                                                                                                                               default '0'                           not null,
    Redeemed          tinyint unsigned                                                                                                                                                                                                                                                                                                               default '0'                           not null,
    Source            enum ('CSV', 'ADMIN') charset utf8                                                                                                                                                                                                                                                                                             default 'CSV'                         null,
    EmailSentDate     datetime                                                                                                                                                                                                                                                                                                                                                             null,
    SummitID          int                                                                                                                                                                                                                                                                                                                                                                  null,
    CreatorID         int                                                                                                                                                                                                                                                                                                                                                                  null,
    ExternalId        varchar(255) charset utf8                                                                                                                                                                                                                                                                                                                                            null,
    QuantityAvailable int                                                                                                                                                                                                                                                                                                                            default 0                             not null,
    QuantityUsed      int                                                                                                                                                                                                                                                                                                                            default 0                             not null,
    ValidSinceDate    datetime                                                                                                                                                                                                                                                                                                                                                             null,
    ValidUntilDate    datetime                                                                                                                                                                                                                                                                                                                                                             null,
    BadgeTypeID       int                                                                                                                                                                                                                                                                                                                                                                  null,
    constraint SummitID_Code
        unique (SummitID, Code)
)
    charset = latin1;

create table MemberSummitRegistrationDiscountCode
(
    ID        int auto_increment
        primary key,
    FirstName varchar(50) charset utf8                                                   null,
    LastName  varchar(50) charset utf8                                                   null,
    Email     varchar(50) charset utf8                                                   null,
    Type      enum ('VIP', 'ATC', 'MEDIA ANALYST', 'SPONSOR') charset utf8 default 'VIP' null,
    OwnerID   int                                                                        null,
    constraint FK_4A51DE511D3633A
        foreign key (ID) references SummitRegistrationPromoCode (ID)
            on delete cascade
)
    charset = latin1;

create index OwnerID
    on MemberSummitRegistrationDiscountCode (OwnerID);

create table MemberSummitRegistrationPromoCode
(
    ID        int auto_increment
        primary key,
    FirstName varchar(50) charset utf8                                                   null,
    LastName  varchar(50) charset utf8                                                   null,
    Email     varchar(254) charset utf8                                                  null,
    Type      enum ('VIP', 'ATC', 'MEDIA ANALYST', 'SPONSOR') charset utf8 default 'VIP' null,
    OwnerID   int                                                                        null,
    constraint FK_MemberSummitRegistrationPromoCode_PromoCode
        foreign key (ID) references SummitRegistrationPromoCode (ID)
            on delete cascade
)
    charset = latin1;

create index OwnerID
    on MemberSummitRegistrationPromoCode (OwnerID);

create table SpeakerSummitRegistrationDiscountCode
(
    ID        int auto_increment
        primary key,
    Type      enum ('ACCEPTED', 'ALTERNATE') charset utf8 default 'ACCEPTED' null,
    SpeakerID int                                                            null,
    constraint FK_335080B611D3633A
        foreign key (ID) references SummitRegistrationPromoCode (ID)
            on delete cascade
)
    charset = latin1;

create index SpeakerID
    on SpeakerSummitRegistrationDiscountCode (SpeakerID);

create table SpeakerSummitRegistrationPromoCode
(
    ID        int auto_increment
        primary key,
    Type      enum ('ACCEPTED', 'ALTERNATE') charset utf8 default 'ACCEPTED' null,
    SpeakerID int                                                            null,
    constraint FK_2E203D4011D3633A
        foreign key (ID) references SummitRegistrationPromoCode (ID)
            on delete cascade
)
    charset = latin1;

create index SpeakerID
    on SpeakerSummitRegistrationPromoCode (SpeakerID);

create table SponsorSummitRegistrationDiscountCode
(
    ID        int auto_increment
        primary key,
    SponsorID int null,
    constraint FK_SponsorSummitRegistrationDiscountCode_PromoCode
        foreign key (ID) references SummitRegistrationPromoCode (ID)
            on delete cascade
)
    charset = latin1;

create index SponsorID
    on SponsorSummitRegistrationDiscountCode (SponsorID);

create table SponsorSummitRegistrationPromoCode
(
    ID        int auto_increment
        primary key,
    SponsorID int null,
    constraint FK_SponsorSummitRegistrationPromoCode_PromoCode
        foreign key (ID) references SummitRegistrationPromoCode (ID)
            on delete cascade
)
    charset = latin1;

create index SponsorID
    on SponsorSummitRegistrationPromoCode (SponsorID);

create table SummitRegistrationDiscountCode
(
    ID             int auto_increment
        primary key,
    DiscountRate   decimal(9, 2) default 0.00 not null,
    DiscountAmount decimal(9, 2) default 0.00 not null,
    constraint FK_SummitRegistrationDiscountCode_PromoCode
        foreign key (ID) references SummitRegistrationPromoCode (ID)
            on delete cascade
)
    charset = latin1;

create index BadgeTypeID
    on SummitRegistrationPromoCode (BadgeTypeID);

create index ClassName
    on SummitRegistrationPromoCode (ClassName);

create index CreatorID
    on SummitRegistrationPromoCode (CreatorID);

create index SummitID
    on SummitRegistrationPromoCode (SummitID);

create table SummitRegistrationPromoCode_AllowedTicketTypes
(
    ID                            int auto_increment
        primary key,
    SummitRegistrationPromoCodeID int default 0 not null,
    SummitTicketTypeID            int default 0 not null
)
    charset = latin1;

create index SummitRegistrationPromoCodeID
    on SummitRegistrationPromoCode_AllowedTicketTypes (SummitRegistrationPromoCodeID);

create index SummitTicketTypeID
    on SummitRegistrationPromoCode_AllowedTicketTypes (SummitTicketTypeID);

create table SummitRegistrationPromoCode_BadgeFeatures
(
    ID                            int auto_increment
        primary key,
    SummitRegistrationPromoCodeID int default 0 not null,
    SummitBadgeFeatureTypeID      int default 0 not null
)
    charset = latin1;

create index SummitBadgeFeatureTypeID
    on SummitRegistrationPromoCode_BadgeFeatures (SummitBadgeFeatureTypeID);

create index SummitRegistrationPromoCodeID
    on SummitRegistrationPromoCode_BadgeFeatures (SummitRegistrationPromoCodeID);

create table SummitReport
(
    ID          int auto_increment
        primary key,
    ClassName   enum ('SummitReport') charset utf8 default 'SummitReport' null,
    LastEdited  datetime                                                  null,
    Created     datetime                                                  null,
    Name        mediumtext charset utf8                                   null,
    Description mediumtext charset utf8                                   null
)
    charset = latin1;

create index ClassName
    on SummitReport (ClassName);

create table SummitReportConfig
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('SummitReportConfig') charset utf8 default 'SummitReportConfig' null,
    LastEdited datetime                                                              null,
    Created    datetime                                                              null,
    Name       mediumtext charset utf8                                               null,
    Value      mediumtext charset utf8                                               null,
    ReportID   int                                                                   null
)
    charset = latin1;

create index ClassName
    on SummitReportConfig (ClassName);

create index ReportID
    on SummitReportConfig (ReportID);

create table SummitRoomReservation
(
    ID                        int auto_increment
        primary key,
    ClassName                 enum ('SummitRoomReservation') charset utf8                                                default 'SummitRoomReservation' null,
    LastEdited                datetime                                                                                                                   null,
    Created                   datetime                                                                                                                   null,
    StartDateTime             datetime                                                                                                                   null,
    EndDateTime               datetime                                                                                                                   null,
    Status                    enum ('Reserved', 'Error', 'Paid', 'RequestedRefund', 'Refunded', 'Canceled') charset utf8 default 'Reserved'              null,
    PaymentGatewayCartId      varchar(512) charset utf8                                                                                                  null,
    PaymentGatewayClientToken mediumtext charset utf8                                                                                                    null,
    Currency                  varchar(3) charset utf8                                                                                                    null,
    Amount                    int                                                                                        default 0                       not null,
    RefundedAmount            int                                                                                        default 0                       not null,
    ApprovedPaymentDate       datetime                                                                                                                   null,
    LastError                 mediumtext charset utf8                                                                                                    null,
    OwnerID                   int                                                                                                                        null,
    RoomID                    int                                                                                                                        null
)
    charset = latin1;

create index ClassName
    on SummitRoomReservation (ClassName);

create index OwnerID
    on SummitRoomReservation (OwnerID);

create index RoomID
    on SummitRoomReservation (RoomID);

create table SummitScheduleConfig
(
    ID                           int auto_increment
        primary key,
    Created                      datetime                                                                                 not null,
    LastEdited                   datetime                                                                                 not null,
    ClassName                    enum ('SummitScheduleConfig') charset utf8                default 'SummitScheduleConfig' null,
    `Key`                        varchar(255)                                              default 'Default'              not null,
    ColorSource                  enum ('EVENT_TYPES', 'TRACK', 'TRACK_GROUP') charset utf8 default 'EVENT_TYPES'          null,
    IsEnabled                    tinyint(1)                                                default 1                      not null,
    IsMySchedule                 tinyint(1)                                                default 0                      not null,
    OnlyEventsWithAttendeeAccess tinyint(1)                                                default 0                      not null,
    SummitID                     int                                                                                      null,
    IsDefault                    tinyint(1)                                                default 0                      not null,
    constraint Summit_Key
        unique (SummitID, `Key`),
    constraint FK_97BF395C90CF7278
        foreign key (SummitID) references Summit (ID)
            on delete cascade
)
    collate = utf8_unicode_ci;

create index SummitID
    on SummitScheduleConfig (SummitID);

create table SummitScheduleFilterElementConfig
(
    ID                     int auto_increment
        primary key,
    Created                datetime                                                                                                                                                                                              not null,
    LastEdited             datetime                                                                                                                                                                                              not null,
    ClassName              enum ('SummitScheduleFilterElementConfig') charset utf8                                                                                                   default 'SummitScheduleFilterElementConfig' null,
    Type                   enum ('DATE', 'TRACK', 'TRACK_GROUPS', 'COMPANY', 'LEVEL', 'SPEAKERS', 'VENUES', 'EVENT_TYPES', 'TITLE', 'CUSTOM_ORDER', 'ABSTRACT', 'TAGS') charset utf8 default 'DATE'                              null,
    Label                  varchar(255)                                                                                                                                                                                          not null,
    IsEnabled              tinyint(1)                                                                                                                                                default 1                                   not null,
    PrefilterValues        longtext                                                                                                                                                                                              null,
    SummitScheduleConfigID int                                                                                                                                                                                                   null,
    constraint SummitScheduleConfig_Type
        unique (SummitScheduleConfigID, Type),
    constraint FK_F95F239058D86ED5
        foreign key (SummitScheduleConfigID) references SummitScheduleConfig (ID)
            on delete cascade
)
    collate = utf8_unicode_ci;

create index SummitScheduleConfigID
    on SummitScheduleFilterElementConfig (SummitScheduleConfigID);

create table SummitScheduleGlobalSearchTerm
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('SummitScheduleGlobalSearchTerm') charset utf8 default 'SummitScheduleGlobalSearchTerm' null,
    LastEdited datetime                                                                                      null,
    Created    datetime                                                                                      null,
    Term       mediumtext charset utf8                                                                       null,
    Hits       int                                                  default 0                                not null,
    SummitID   int                                                                                           null
)
    charset = latin1;

create index ClassName
    on SummitScheduleGlobalSearchTerm (ClassName);

create index SummitID
    on SummitScheduleGlobalSearchTerm (SummitID);

create table SummitSchedulePreFilterElementConfig
(
    ID                     int auto_increment
        primary key,
    Created                datetime                                                                                                                                                                                                 not null,
    LastEdited             datetime                                                                                                                                                                                                 not null,
    ClassName              enum ('SummitSchedulePreFilterElementConfig') charset utf8                                                                                                default 'SummitSchedulePreFilterElementConfig' null,
    Type                   enum ('DATE', 'TRACK', 'TRACK_GROUPS', 'COMPANY', 'LEVEL', 'SPEAKERS', 'VENUES', 'EVENT_TYPES', 'TITLE', 'CUSTOM_ORDER', 'ABSTRACT', 'TAGS') charset utf8 default 'DATE'                                 null,
    `Values`               longtext                                                                                                                                                                                                 null,
    SummitScheduleConfigID int                                                                                                                                                                                                      null,
    constraint SummitScheduleConfig_Type
        unique (SummitScheduleConfigID, Type),
    constraint FK_AC25329C58D86ED5
        foreign key (SummitScheduleConfigID) references SummitScheduleConfig (ID)
            on delete cascade
)
    collate = utf8_unicode_ci;

create index SummitScheduleConfigID
    on SummitSchedulePreFilterElementConfig (SummitScheduleConfigID);

create table SummitSelectedPresentationList
(
    ID              int auto_increment
        primary key,
    ClassName       enum ('SummitSelectedPresentationList') charset utf8 default 'SummitSelectedPresentationList' null,
    LastEdited      datetime                                                                                      null,
    Created         datetime                                                                                      null,
    Name            mediumtext charset utf8                                                                       null,
    ListType        enum ('Individual', 'Group') charset utf8            default 'Individual'                     null,
    ListClass       enum ('Session', 'Lightning') charset utf8           default 'Session'                        null,
    Hash            varchar(50) charset utf8                                                                      null,
    CategoryID      int                                                                                           null,
    MemberID        int                                                                                           null,
    SelectionPlanID int                                                                                           null,
    constraint FK_SummitSelectedPresentationList_Member
        foreign key (MemberID) references Member (ID)
            on delete cascade,
    constraint FK_SummitSelectedPresentationList_SelectionPlan
        foreign key (SelectionPlanID) references SelectionPlan (ID)
            on delete cascade,
    constraint FK_SummitSelectedPresentationList_Track
        foreign key (CategoryID) references PresentationCategory (ID)
            on delete cascade
)
    charset = latin1;

create table SummitSelectedPresentation
(
    ID                               int auto_increment
        primary key,
    ClassName                        enum ('SummitSelectedPresentation') charset utf8 default 'SummitSelectedPresentation' null,
    LastEdited                       datetime                                                                              null,
    Created                          datetime                                                                              null,
    `Order`                          int                                              default 0                            not null,
    Collection                       enum ('maybe', 'selected', 'pass') charset utf8  default 'maybe'                      null,
    SummitSelectedPresentationListID int                                                                                   null,
    PresentationID                   int                                                                                   null,
    MemberID                         int                                                                                   null,
    constraint FK_SummitSelectedPresentation_Member
        foreign key (MemberID) references Member (ID)
            on delete cascade,
    constraint FK_SummitSelectedPresentation_Presentation
        foreign key (PresentationID) references Presentation (ID)
            on delete cascade,
    constraint FK_SummitSelectedPresentation_SummitSelectedPresentationList
        foreign key (SummitSelectedPresentationListID) references SummitSelectedPresentationList (ID)
            on delete cascade
)
    charset = latin1;

create index ClassName
    on SummitSelectedPresentation (ClassName);

create index MemberID
    on SummitSelectedPresentation (MemberID);

create index PresentationID
    on SummitSelectedPresentation (PresentationID);

create index SummitSelectedPresentationListID
    on SummitSelectedPresentation (SummitSelectedPresentationListID);

create index SummitSelectedPresentation_Presentation_List_Unique
    on SummitSelectedPresentation (PresentationID, SummitSelectedPresentationListID);

create index CategoryID
    on SummitSelectedPresentationList (CategoryID);

create index ClassName
    on SummitSelectedPresentationList (ClassName);

create index MemberID
    on SummitSelectedPresentationList (MemberID);

create index SelectionPlanID
    on SummitSelectedPresentationList (SelectionPlanID);

create table SummitSelectionPlanExtraQuestionType
(
    ID              int auto_increment
        primary key,
    SelectionPlanID int null,
    constraint FK_7AA38C2FB172E6EC
        foreign key (SelectionPlanID) references SelectionPlan (ID)
            on delete cascade,
    constraint JT_SummitSelectionPlanExtraQuestionType_ExtraQuestionType
        foreign key (ID) references ExtraQuestionType (ID)
            on delete cascade
)
    collate = utf8_unicode_ci;

create index SelectionPlanID
    on SummitSelectionPlanExtraQuestionType (SelectionPlanID);

create table SummitSponsorMetric
(
    ID        int auto_increment
        primary key,
    ClassName varchar(255) default 'SummitSponsorMetric' not null,
    SponsorID int                                        null,
    constraint FK_8AFBB25E94CE1A1A
        foreign key (SponsorID) references Sponsor (ID)
            on delete cascade,
    constraint FK_SummitSponsorMetricc_SummitMetric
        foreign key (ID) references SummitMetric (ID)
            on delete cascade
)
    collate = utf8_unicode_ci;

create index SponsorID
    on SummitSponsorMetric (SponsorID);

create table SummitSponsorPage
(
    ID                                  int auto_increment
        primary key,
    SponsorIntro                        mediumtext charset utf8      null,
    SponsorAlert                        mediumtext charset utf8      null,
    SponsorContract                     mediumtext charset utf8      null,
    SponsorProspectus                   mediumtext charset utf8      null,
    CallForSponsorShipStartDate         datetime                     null,
    CallForSponsorShipEndDate           datetime                     null,
    AudienceIntro                       mediumtext charset utf8      null,
    ShowAudience                        tinyint unsigned default '0' not null,
    AudienceMetricsTitle                mediumtext charset utf8      null,
    AudienceTotalSummitAttendees        mediumtext charset utf8      null,
    AudienceCompaniesRepresented        mediumtext charset utf8      null,
    AudienceCountriesRepresented        mediumtext charset utf8      null,
    HowToSponsorContent                 mediumtext charset utf8      null,
    VenueMapContent                     mediumtext charset utf8      null,
    SponsorshipPackagesTitle            mediumtext charset utf8      null,
    ConditionalSponsorshipPackagesTitle mediumtext charset utf8      null,
    SponsorshipAddOnsTitle              mediumtext charset utf8      null,
    CrowdImageID                        int                          null,
    ExhibitImageID                      int                          null
)
    charset = latin1;

create index CrowdImageID
    on SummitSponsorPage (CrowdImageID);

create index ExhibitImageID
    on SummitSponsorPage (ExhibitImageID);

create table SummitSponsorPage_Live
(
    ID                                  int auto_increment
        primary key,
    SponsorIntro                        mediumtext charset utf8      null,
    SponsorAlert                        mediumtext charset utf8      null,
    SponsorContract                     mediumtext charset utf8      null,
    SponsorProspectus                   mediumtext charset utf8      null,
    CallForSponsorShipStartDate         datetime                     null,
    CallForSponsorShipEndDate           datetime                     null,
    AudienceIntro                       mediumtext charset utf8      null,
    ShowAudience                        tinyint unsigned default '0' not null,
    AudienceMetricsTitle                mediumtext charset utf8      null,
    AudienceTotalSummitAttendees        mediumtext charset utf8      null,
    AudienceCompaniesRepresented        mediumtext charset utf8      null,
    AudienceCountriesRepresented        mediumtext charset utf8      null,
    HowToSponsorContent                 mediumtext charset utf8      null,
    VenueMapContent                     mediumtext charset utf8      null,
    SponsorshipPackagesTitle            mediumtext charset utf8      null,
    ConditionalSponsorshipPackagesTitle mediumtext charset utf8      null,
    SponsorshipAddOnsTitle              mediumtext charset utf8      null,
    CrowdImageID                        int                          null,
    ExhibitImageID                      int                          null
)
    charset = latin1;

create index CrowdImageID
    on SummitSponsorPage_Live (CrowdImageID);

create index ExhibitImageID
    on SummitSponsorPage_Live (ExhibitImageID);

create table SummitSponsorPage_versions
(
    ID                                  int auto_increment
        primary key,
    RecordID                            int              default 0   not null,
    Version                             int              default 0   not null,
    SponsorIntro                        mediumtext charset utf8      null,
    SponsorAlert                        mediumtext charset utf8      null,
    SponsorContract                     mediumtext charset utf8      null,
    SponsorProspectus                   mediumtext charset utf8      null,
    CallForSponsorShipStartDate         datetime                     null,
    CallForSponsorShipEndDate           datetime                     null,
    AudienceIntro                       mediumtext charset utf8      null,
    ShowAudience                        tinyint unsigned default '0' not null,
    AudienceMetricsTitle                mediumtext charset utf8      null,
    AudienceTotalSummitAttendees        mediumtext charset utf8      null,
    AudienceCompaniesRepresented        mediumtext charset utf8      null,
    AudienceCountriesRepresented        mediumtext charset utf8      null,
    HowToSponsorContent                 mediumtext charset utf8      null,
    VenueMapContent                     mediumtext charset utf8      null,
    SponsorshipPackagesTitle            mediumtext charset utf8      null,
    ConditionalSponsorshipPackagesTitle mediumtext charset utf8      null,
    SponsorshipAddOnsTitle              mediumtext charset utf8      null,
    CrowdImageID                        int                          null,
    ExhibitImageID                      int                          null,
    constraint RecordID_Version
        unique (RecordID, Version)
)
    charset = latin1;

create index CrowdImageID
    on SummitSponsorPage_versions (CrowdImageID);

create index ExhibitImageID
    on SummitSponsorPage_versions (ExhibitImageID);

create index RecordID
    on SummitSponsorPage_versions (RecordID);

create index Version
    on SummitSponsorPage_versions (Version);

create table SummitTaxType
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('SummitTaxType') charset utf8 default 'SummitTaxType' null,
    LastEdited datetime                                                    null,
    Created    datetime                                                    null,
    Name       varchar(255) charset utf8                                   null,
    TaxID      varchar(255) charset utf8                                   null,
    Rate       decimal(9, 2)                       default 0.00            not null,
    SummitID   int                                                         null
)
    charset = latin1;

create index ClassName
    on SummitTaxType (ClassName);

create index SummitID
    on SummitTaxType (SummitID);

create table SummitTicketType
(
    ID                        int auto_increment
        primary key,
    ClassName                 enum ('SummitTicketType') charset utf8 default 'SummitTicketType' null,
    LastEdited                datetime                                                          null,
    Created                   datetime                                                          null,
    ExternalId                varchar(255) charset utf8                                         null,
    Name                      mediumtext charset utf8                                           null,
    Description               mediumtext charset utf8                                           null,
    SummitID                  int                                                               null,
    Cost                      decimal(9, 2)                          default 0.00               not null,
    Currency                  varchar(3) charset utf8                                           null,
    QuantityToSell            int                                    default 0                  not null,
    QuantitySold              int                                    default 0                  not null,
    MaxQuantityToSellPerOrder int                                    default 0                  not null,
    SaleStartDate             datetime                                                          null,
    SaleEndDate               datetime                                                          null,
    BadgeTypeID               int                                                               null,
    constraint FK_SummitTicketType_Summitt
        foreign key (SummitID) references Summit (ID)
            on delete cascade
)
    charset = latin1;

create table SummitAttendeeTicket
(
    ID                    int auto_increment
        primary key,
    ClassName             enum ('SummitAttendeeTicket') charset utf8                                                      default 'SummitAttendeeTicket' null,
    LastEdited            datetime                                                                                                                       null on update CURRENT_TIMESTAMP,
    Created               datetime                                                                                                                       null,
    ExternalOrderId       varchar(255) charset utf8                                                                                                      null,
    ExternalAttendeeId    varchar(255) charset utf8                                                                                                      null,
    TicketBoughtDate      datetime                                                                                                                       null,
    TicketChangedDate     datetime                                                                                                                       null,
    TicketTypeID          int                                                                                                                            null,
    OwnerID               int                                                                                                                            null,
    Status                enum ('Reserved', 'Cancelled', 'RefundRequested', 'Refunded', 'Confirmed', 'Paid') charset utf8 default 'Reserved'             null,
    Number                varchar(255) charset utf8                                                                                                      null,
    RawCost               decimal(9, 2)                                                                                   default 0.00                   not null,
    Discount              decimal(9, 2)                                                                                   default 0.00                   not null,
    RefundedAmount        decimal(9, 2)                                                                                   default 0.00                   not null,
    Currency              varchar(3) charset utf8                                                                                                        null,
    QRCode                varchar(255) charset utf8                                                                                                      null,
    Hash                  varchar(255) charset utf8                                                                                                      null,
    HashCreationDate      datetime                                                                                                                       null,
    SummitAttendeeBadgeID int                                                                                                                            null,
    OrderID               int                                                                                                                            null,
    PromoCodeID           int                                                                                                                            null,
    IsActive              tinyint(1)                                                                                      default 1                      not null,
    constraint FK_SummitAttendeeTicket_Badge
        foreign key (SummitAttendeeBadgeID) references SummitAttendeeBadge (ID)
            on delete cascade,
    constraint FK_SummitAttendeeTicket_ORDER
        foreign key (OrderID) references SummitOrder (ID)
            on delete cascade,
    constraint FK_SummitAttendeeTicket_Owner
        foreign key (OwnerID) references SummitAttendee (ID)
            on delete set null,
    constraint FK_SummitAttendeeTicket_PromoCode
        foreign key (PromoCodeID) references SummitRegistrationPromoCode (ID)
            on delete set null,
    constraint FK_SummitAttendeeTicket_Type
        foreign key (TicketTypeID) references SummitTicketType (ID)
            on delete cascade
)
    charset = latin1;

create index ClassName
    on SummitAttendeeTicket (ClassName);

create index OrderID
    on SummitAttendeeTicket (OrderID);

create index Order_Attendee
    on SummitAttendeeTicket (ExternalOrderId, ExternalAttendeeId);

create index OwnerID
    on SummitAttendeeTicket (OwnerID);

create index PromoCodeID
    on SummitAttendeeTicket (PromoCodeID);

create index SummitAttendeeBadgeID
    on SummitAttendeeTicket (SummitAttendeeBadgeID);

create index TicketTypeID
    on SummitAttendeeTicket (TicketTypeID);

create table SummitAttendeeTicketFormerHash
(
    ID                     int auto_increment
        primary key,
    Created                datetime     not null,
    LastEdited             datetime     not null,
    Hash                   varchar(255) null,
    SummitAttendeeTicketID int          null,
    constraint Hash
        unique (Hash),
    constraint FK_75D2F561D637E86A
        foreign key (SummitAttendeeTicketID) references SummitAttendeeTicket (ID)
            on delete cascade
)
    collate = utf8_unicode_ci;

create index SummitAttendeeTicketID
    on SummitAttendeeTicketFormerHash (SummitAttendeeTicketID);

create table SummitAttendeeTicketRefundRequest
(
    ID       int not null
        primary key,
    TicketID int null,
    constraint FK_A6F6E11611D3633A
        foreign key (ID) references SummitRefundRequest (ID)
            on delete cascade,
    constraint FK_SummitAttendeeTicketRefundRequest_SummitRefundRequest
        foreign key (TicketID) references SummitAttendeeTicket (ID)
            on delete cascade
)
    collate = utf8_unicode_ci;

create index TicketID
    on SummitAttendeeTicketRefundRequest (TicketID);

create index BadgeTypeID
    on SummitTicketType (BadgeTypeID);

create index ClassName
    on SummitTicketType (ClassName);

create index SummitID
    on SummitTicketType (SummitID);

create index Summit_ExternalId
    on SummitTicketType (SummitID, ExternalId);

create table SummitTicketType_Taxes
(
    ID                 int auto_increment
        primary key,
    SummitTicketTypeID int default 0 not null,
    SummitTaxTypeID    int default 0 not null
)
    charset = latin1;

create index SummitTaxTypeID
    on SummitTicketType_Taxes (SummitTaxTypeID);

create index SummitTicketTypeID
    on SummitTicketType_Taxes (SummitTicketTypeID);

create table SummitTrackChair
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('SummitTrackChair') charset utf8 default 'SummitTrackChair' null,
    LastEdited datetime                                                          null,
    Created    datetime                                                          null,
    MemberID   int                                                               null,
    SummitID   int                                                               null,
    constraint SummitTrackChair_Member_Summit
        unique (MemberID, SummitID)
)
    charset = latin1;

create index ClassName
    on SummitTrackChair (ClassName);

create index MemberID
    on SummitTrackChair (MemberID);

create index SummitID
    on SummitTrackChair (SummitID);

create table SummitTrackChair_Categories
(
    ID                     int auto_increment
        primary key,
    SummitTrackChairID     int default 0 not null,
    PresentationCategoryID int default 0 not null,
    constraint SummitTrackChair_Categories_TrackChairID_CategoryID
        unique (SummitTrackChairID, PresentationCategoryID)
)
    charset = latin1;

create index PresentationCategoryID
    on SummitTrackChair_Categories (PresentationCategoryID);

create index SummitTrackChairID
    on SummitTrackChair_Categories (SummitTrackChairID);

create table SummitType
(
    ID           int auto_increment
        primary key,
    ClassName    enum ('SummitType') charset utf8 default 'SummitType' null,
    LastEdited   datetime                                              null,
    Created      datetime                                              null,
    FriendlyName mediumtext charset utf8                               null,
    Description  mediumtext charset utf8                               null,
    Audience     mediumtext charset utf8                               null,
    Color        mediumtext charset utf8                               null,
    Type         varchar(100) charset utf8                             null
)
    charset = latin1;

create index ClassName
    on SummitType (ClassName);

create table SummitUpdate
(
    ID                  int auto_increment
        primary key,
    ClassName           enum ('SummitUpdate') charset utf8                              default 'SummitUpdate' null,
    LastEdited          datetime                                                                               null,
    Created             datetime                                                                               null,
    Title               mediumtext charset utf8                                                                null,
    Category            enum ('News', 'Speakers', 'Sponsors', 'Attendees') charset utf8 default 'News'         null,
    Description         mediumtext charset utf8                                                                null,
    `Order`             int                                                             default 0              not null,
    SummitUpdatesPageID int                                                                                    null,
    ImageID             int                                                                                    null
)
    charset = latin1;

create index ClassName
    on SummitUpdate (ClassName);

create index ImageID
    on SummitUpdate (ImageID);

create index SummitUpdatesPageID
    on SummitUpdate (SummitUpdatesPageID);

create table SummitVenue
(
    ID     int auto_increment
        primary key,
    IsMain tinyint unsigned default '0' not null,
    constraint FK_6496127911D3633A
        foreign key (ID) references SummitAbstractLocation (ID)
            on delete cascade
)
    charset = latin1;

create table SummitVenueFloor
(
    ID          int auto_increment
        primary key,
    ClassName   enum ('SummitVenueFloor') charset utf8 default 'SummitVenueFloor' null,
    LastEdited  datetime                                                          null,
    Created     datetime                                                          null,
    Name        varchar(50) charset utf8                                          null,
    Description mediumtext charset utf8                                           null,
    Number      int                                    default 0                  not null,
    VenueID     int                                                               null,
    ImageID     int                                                               null
)
    charset = latin1;

create index ClassName
    on SummitVenueFloor (ClassName);

create index ImageID
    on SummitVenueFloor (ImageID);

create index VenueID
    on SummitVenueFloor (VenueID);

create table SummitVenueRoom
(
    ID                int auto_increment
        primary key,
    Capacity          int              default 0   not null,
    OverrideBlackouts tinyint unsigned default '0' not null,
    VenueID           int                          null,
    FloorID           int                          null,
    ImageID           int                          null,
    constraint FK_SummitVenueRoomSummitAbstractLocation
        foreign key (ID) references SummitAbstractLocation (ID)
            on delete cascade
)
    charset = latin1;

create index FloorID
    on SummitVenueRoom (FloorID);

create index ImageID
    on SummitVenueRoom (ImageID);

create index VenueID
    on SummitVenueRoom (VenueID);

create table SummitWIFIConnection
(
    ID          int auto_increment
        primary key,
    ClassName   enum ('SummitWIFIConnection') charset utf8 default 'SummitWIFIConnection' null,
    LastEdited  datetime                                                                  null,
    Created     datetime                                                                  null,
    SSID        mediumtext charset utf8                                                   null,
    Password    mediumtext charset utf8                                                   null,
    Description mediumtext charset utf8                                                   null,
    SummitID    int                                                                       null
)
    charset = latin1;

create index ClassName
    on SummitWIFIConnection (ClassName);

create index SummitID
    on SummitWIFIConnection (SummitID);

create table Summit_ExcludedCategoriesForAcceptedPresentations
(
    ID                     int auto_increment
        primary key,
    SummitID               int default 0 not null,
    PresentationCategoryID int default 0 not null
)
    charset = latin1;

create index PresentationCategoryID
    on Summit_ExcludedCategoriesForAcceptedPresentations (PresentationCategoryID);

create index SummitID
    on Summit_ExcludedCategoriesForAcceptedPresentations (SummitID);

create table Summit_ExcludedCategoriesForAlternatePresentations
(
    ID                     int auto_increment
        primary key,
    SummitID               int default 0 not null,
    PresentationCategoryID int default 0 not null
)
    charset = latin1;

create index PresentationCategoryID
    on Summit_ExcludedCategoriesForAlternatePresentations (PresentationCategoryID);

create index SummitID
    on Summit_ExcludedCategoriesForAlternatePresentations (SummitID);

create table Summit_ExcludedCategoriesForRejectedPresentations
(
    ID                     int auto_increment
        primary key,
    SummitID               int default 0 not null,
    PresentationCategoryID int default 0 not null
)
    charset = latin1;

create index PresentationCategoryID
    on Summit_ExcludedCategoriesForRejectedPresentations (PresentationCategoryID);

create index SummitID
    on Summit_ExcludedCategoriesForRejectedPresentations (SummitID);

create table Summit_ExcludedTracksForUploadPresentationSlideDeck
(
    ID                     int auto_increment
        primary key,
    SummitID               int default 0 not null,
    PresentationCategoryID int default 0 not null
)
    charset = latin1;

create index PresentationCategoryID
    on Summit_ExcludedTracksForUploadPresentationSlideDeck (PresentationCategoryID);

create index SummitID
    on Summit_ExcludedTracksForUploadPresentationSlideDeck (SummitID);

create table Summit_FeaturedSpeakers
(
    ID                    int auto_increment
        primary key,
    SummitID              int           null,
    PresentationSpeakerID int           null,
    `Order`               int default 1 not null,
    constraint UNIQ_FFDEADE990CF727855E7310E
        unique (SummitID, PresentationSpeakerID)
)
    collate = utf8_unicode_ci;

create index PresentationSpeakerID
    on Summit_FeaturedSpeakers (PresentationSpeakerID);

create index SummitID
    on Summit_FeaturedSpeakers (SummitID);

create table Summit_PublishedPresentationTypes
(
    ID                 int auto_increment
        primary key,
    SummitID           int default 0 not null,
    PresentationTypeID int default 0 not null
)
    charset = latin1;

create index PresentationTypeID
    on Summit_PublishedPresentationTypes (PresentationTypeID);

create index SummitID
    on Summit_PublishedPresentationTypes (SummitID);

create table Summit_RegistrationCompanies
(
    ID        int auto_increment
        primary key,
    SummitID  int null,
    CompanyID int null,
    constraint SummitID_CompanyID
        unique (SummitID, CompanyID),
    constraint FK_RegistrationCompanies_Company
        foreign key (CompanyID) references Company (ID)
            on delete cascade,
    constraint FK_RegistrationCompanies_Summit
        foreign key (SummitID) references Summit (ID)
            on delete cascade
)
    collate = utf8_unicode_ci;

create index CompanyID
    on Summit_RegistrationCompanies (CompanyID);

create index SummitID
    on Summit_RegistrationCompanies (SummitID);

create table Summit_RegularPresentationTypes
(
    ID                 int auto_increment
        primary key,
    SummitID           int default 0 not null,
    PresentationTypeID int default 0 not null
)
    charset = latin1;

create index PresentationTypeID
    on Summit_RegularPresentationTypes (PresentationTypeID);

create index SummitID
    on Summit_RegularPresentationTypes (SummitID);

create table SupportChannelType
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('SupportChannelType') charset utf8 default 'SupportChannelType' null,
    LastEdited datetime                                                              null,
    Created    datetime                                                              null,
    Type       varchar(50) charset utf8                                              null,
    IconID     int                                                                   null,
    constraint Type
        unique (Type)
)
    charset = latin1;

create index ClassName
    on SupportChannelType (ClassName);

create index IconID
    on SupportChannelType (IconID);

create table SupportingCompany
(
    ID                       int auto_increment
        primary key,
    `Order`                  int default 1 not null,
    CompanyID                int           null,
    ProjectSponsorshipTypeID int           null,
    constraint FK_487453A4802D9F89
        foreign key (ProjectSponsorshipTypeID) references ProjectSponsorshipType (ID)
            on delete cascade,
    constraint FK_487453A49D1F4548
        foreign key (CompanyID) references Company (ID)
            on delete cascade
)
    collate = utf8_unicode_ci;

create index CompanyID
    on SupportingCompany (CompanyID);

create index ProjectSponsorshipTypeID
    on SupportingCompany (ProjectSponsorshipTypeID);

create table Survey
(
    ID               int auto_increment
        primary key,
    ClassName        enum ('Survey', 'EntitySurvey') charset utf8          default 'Survey'     null,
    LastEdited       datetime                                                                   null,
    Created          datetime                                                                   null,
    BeenEmailed      tinyint unsigned                                      default '0'          not null,
    IsTest           tinyint unsigned                                      default '0'          not null,
    State            enum ('INCOMPLETE', 'SAVED', 'COMPLETE') charset utf8 default 'INCOMPLETE' null,
    Lang             varchar(10) charset utf8                                                   null,
    IsMigrated       tinyint unsigned                                      default '0'          not null,
    TemplateID       int                                                                        null,
    CreatedByID      int                                                                        null,
    CurrentStepID    int                                                                        null,
    MaxAllowedStepID int                                                                        null
)
    charset = latin1;

create index ClassName
    on Survey (ClassName);

create index CreatedByID
    on Survey (CreatedByID);

create index CurrentStepID
    on Survey (CurrentStepID);

create index MaxAllowedStepID
    on Survey (MaxAllowedStepID);

create index TemplateID
    on Survey (TemplateID);

create table SurveyAnswer
(
    ID          int auto_increment
        primary key,
    ClassName   enum ('SurveyAnswer') charset utf8 default 'SurveyAnswer' null,
    LastEdited  datetime                                                  null,
    Created     datetime                                                  null,
    Value       mediumtext charset utf8                                   null,
    QuestionID  int                                                       null,
    StepID      int                                                       null,
    UpdatedByID int                                                       null
)
    charset = latin1;

create index ClassName
    on SurveyAnswer (ClassName);

create index QuestionID
    on SurveyAnswer (QuestionID);

create index StepID
    on SurveyAnswer (StepID);

create index UpdatedByID
    on SurveyAnswer (UpdatedByID);

create table SurveyAnswerLog
(
    ID          int auto_increment
        primary key,
    ClassName   enum ('SurveyAnswerLog') charset utf8            default 'SurveyAnswerLog' null,
    LastEdited  datetime                                                                   null,
    Created     datetime                                                                   null,
    FormerValue mediumtext charset utf8                                                    null,
    NewValue    mediumtext charset utf8                                                    null,
    Operation   enum ('INSERT', 'UPDATE', 'DELETE') charset utf8 default 'INSERT'          null,
    QuestionID  int                                                                        null,
    StepID      int                                                                        null,
    SurveyID    int                                                                        null,
    MemberID    int                                                                        null
)
    charset = latin1;

create index ClassName
    on SurveyAnswerLog (ClassName);

create index MemberID
    on SurveyAnswerLog (MemberID);

create index QuestionID
    on SurveyAnswerLog (QuestionID);

create index StepID
    on SurveyAnswerLog (StepID);

create index SurveyID
    on SurveyAnswerLog (SurveyID);

create table SurveyAnswerTag
(
    ID          int auto_increment
        primary key,
    ClassName   enum ('SurveyAnswerTag') charset utf8              default 'SurveyAnswerTag' null,
    LastEdited  datetime                                                                     null,
    Created     datetime                                                                     null,
    Value       mediumtext charset utf8                                                      null,
    Type        enum ('AUTOMATIC', 'CUSTOM', 'REGEX') charset utf8 default 'AUTOMATIC'       null,
    CreatedByID int                                                                          null
)
    charset = latin1;

create index ClassName
    on SurveyAnswerTag (ClassName);

create index CreatedByID
    on SurveyAnswerTag (CreatedByID);

create table SurveyAnswer_Tags
(
    ID                int auto_increment
        primary key,
    SurveyAnswerID    int default 0 not null,
    SurveyAnswerTagID int default 0 not null
)
    charset = latin1;

create index SurveyAnswerID
    on SurveyAnswer_Tags (SurveyAnswerID);

create index SurveyAnswerTagID
    on SurveyAnswer_Tags (SurveyAnswerTagID);

create table SurveyCustomValidationRule
(
    ID             int auto_increment
        primary key,
    CustomJSMethod mediumtext charset utf8 null
)
    charset = latin1;

create table SurveyDoubleEntryTableQuestionTemplate
(
    ID                        int auto_increment
        primary key,
    RowsLabel                 mediumtext charset utf8 null,
    AdditionalRowsLabel       mediumtext charset utf8 null,
    AdditionalRowsDescription mediumtext charset utf8 null
)
    charset = latin1;

create table SurveyDropDownQuestionTemplate
(
    ID                            int auto_increment
        primary key,
    IsMultiSelect                 tinyint unsigned default '0' not null,
    IsCountrySelector             tinyint unsigned default '0' not null,
    UseCountrySelectorExtraOption tinyint unsigned default '0' not null,
    UseChosenPlugin               tinyint unsigned default '0' not null
)
    charset = latin1;

create table SurveyDynamicEntityStep
(
    ID         int auto_increment
        primary key,
    TemplateID int null
)
    charset = latin1;

create index TemplateID
    on SurveyDynamicEntityStep (TemplateID);

create table SurveyDynamicEntityStepTemplate
(
    ID               int auto_increment
        primary key,
    AddEntityText    varchar(255) charset utf8 null,
    DeleteEntityText varchar(255) charset utf8 null,
    EditEntityText   varchar(255) charset utf8 null,
    EntityIconID     int                       null,
    EntityID         int                       null
)
    charset = latin1;

create index EntityID
    on SurveyDynamicEntityStepTemplate (EntityID);

create index EntityIconID
    on SurveyDynamicEntityStepTemplate (EntityIconID);

create table SurveyLiteralContentQuestionTemplate
(
    ID      int auto_increment
        primary key,
    Content mediumtext charset utf8 null
)
    charset = latin1;

create table SurveyMaxLengthValidationRule
(
    ID        int auto_increment
        primary key,
    MaxLength int default 0 not null
)
    charset = latin1;

create table SurveyMinLengthValidationRule
(
    ID        int auto_increment
        primary key,
    MinLength int default 0 not null
)
    charset = latin1;

create table SurveyMultiValueQuestionTemplate
(
    ID                int auto_increment
        primary key,
    EmptyString       varchar(255) charset utf8 null,
    DefaultGroupLabel mediumtext charset utf8   null,
    DefaultValueID    int                       null
)
    charset = latin1;

create index DefaultValueID
    on SurveyMultiValueQuestionTemplate (DefaultValueID);

create table SurveyPage
(
    ID               int auto_increment
        primary key,
    ThankYouText     mediumtext charset utf8 null,
    SurveyTemplateID int                     null
)
    charset = latin1;

create index SurveyTemplateID
    on SurveyPage (SurveyTemplateID);

create table SurveyPage_Live
(
    ID               int auto_increment
        primary key,
    ThankYouText     mediumtext charset utf8 null,
    SurveyTemplateID int                     null
)
    charset = latin1;

create index SurveyTemplateID
    on SurveyPage_Live (SurveyTemplateID);

create table SurveyPage_versions
(
    ID               int auto_increment
        primary key,
    RecordID         int default 0           not null,
    Version          int default 0           not null,
    ThankYouText     mediumtext charset utf8 null,
    SurveyTemplateID int                     null,
    constraint RecordID_Version
        unique (RecordID, Version)
)
    charset = latin1;

create index RecordID
    on SurveyPage_versions (RecordID);

create index SurveyTemplateID
    on SurveyPage_versions (SurveyTemplateID);

create index Version
    on SurveyPage_versions (Version);

create table SurveyQuestionRowValueTemplate
(
    ID           int auto_increment
        primary key,
    IsAdditional tinyint unsigned default '0' not null
)
    charset = latin1;

create table SurveyQuestionTemplate
(
    ID                      int auto_increment
        primary key,
    ClassName               enum ('SurveyQuestionTemplate', 'SurveyLiteralContentQuestionTemplate', 'SurveyMultiValueQuestionTemplate', 'SurveyCheckBoxListQuestionTemplate', 'SurveyDoubleEntryTableQuestionTemplate', 'SurveyRadioButtonMatrixTemplateQuestion', 'SurveyDropDownQuestionTemplate', 'SurveyRadioButtonListQuestionTemplate', 'SurveyRankingQuestionTemplate', 'SurveySingleValueTemplateQuestion', 'SurveyCheckBoxQuestionTemplate', 'SurveyOrganizationQuestionTemplate', 'SurveyTextAreaQuestionTemplate', 'SurveyTextBoxQuestionTemplate', 'SurveyEmailQuestionTemplate', 'SurveyMemberCountryQuestionTemplate', 'SurveyMemberEmailQuestionTemplate', 'SurveyMemberFirstNameQuestionTemplate', 'SurveyMemberLastNameQuestionTemplate', 'SurveyNumericQuestionTemplate', 'SurveyPercentageQuestionTemplate') charset utf8 default 'SurveyQuestionTemplate' null,
    LastEdited              datetime                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          null,
    Created                 datetime                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          null,
    Name                    varchar(255) charset utf8                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         null,
    Label                   mediumtext charset utf8                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           null,
    `Order`                 int                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              default 0                        not null,
    Mandatory               tinyint unsigned                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 default '0'                      not null,
    ReadOnly                tinyint unsigned                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 default '0'                      not null,
    ShowOnSangriaStatistics tinyint unsigned                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 default '0'                      not null,
    ShowOnPublicStatistics  tinyint unsigned                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 default '0'                      not null,
    Hidden                  tinyint unsigned                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 default '0'                      not null,
    StepID                  int                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               null,
    constraint StepID_Name
        unique (StepID, Name)
)
    charset = latin1;

create index ClassName
    on SurveyQuestionTemplate (ClassName);

create index StepID
    on SurveyQuestionTemplate (StepID);

create table SurveyQuestionTemplate_DependsOn
(
    ID                       int auto_increment
        primary key,
    SurveyQuestionTemplateID int                                          default 0         not null,
    ChildID                  int                                          default 0         not null,
    ValueID                  int                                          default 0         not null,
    Operator                 enum ('Equal', 'Not-Equal') charset utf8     default 'Equal'   null,
    Visibility               enum ('Visible', 'Not-Visible') charset utf8 default 'Visible' null,
    BooleanOperatorOnValues  enum ('And', 'Or') charset utf8              default 'And'     null,
    DefaultValue             varchar(254) charset utf8                                      null
)
    charset = latin1;

create index ChildID
    on SurveyQuestionTemplate_DependsOn (ChildID);

create index SurveyQuestionTemplateID
    on SurveyQuestionTemplate_DependsOn (SurveyQuestionTemplateID);

create table SurveyQuestionValueTemplate
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('SurveyQuestionValueTemplate', 'SurveyQuestionColumnValueTemplate', 'SurveyQuestionRowValueTemplate') charset utf8 default 'SurveyQuestionValueTemplate' null,
    LastEdited datetime                                                                                                                                                       null,
    Created    datetime                                                                                                                                                       null,
    Value      varchar(255) charset utf8                                                                                                                                      null,
    `Order`    int                                                                                                                      default 0                             not null,
    Label      mediumtext charset utf8                                                                                                                                        null,
    OwnerID    int                                                                                                                                                            null,
    GroupID    int                                                                                                                                                            null
)
    charset = latin1;

create index ClassName
    on SurveyQuestionValueTemplate (ClassName);

create index GroupID
    on SurveyQuestionValueTemplate (GroupID);

create index OwnerID
    on SurveyQuestionValueTemplate (OwnerID);

create table SurveyQuestionValueTemplateGroup
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('SurveyQuestionValueTemplateGroup') charset utf8 default 'SurveyQuestionValueTemplateGroup' null,
    LastEdited datetime                                                                                          null,
    Created    datetime                                                                                          null,
    Label      mediumtext charset utf8                                                                           null,
    `Order`    int                                                    default 0                                  not null,
    OwnerID    int                                                                                               null
)
    charset = latin1;

create index ClassName
    on SurveyQuestionValueTemplateGroup (ClassName);

create index OwnerID
    on SurveyQuestionValueTemplateGroup (OwnerID);

create table SurveyRadioButtonListQuestionTemplate
(
    ID          int auto_increment
        primary key,
    Orientation enum ('Horizontal', 'Vertical') charset utf8 default 'Vertical' null
)
    charset = latin1;

create table SurveyRangeValidationRule
(
    ID       int auto_increment
        primary key,
    MinRange int default 0 not null,
    MaxRange int default 0 not null
)
    charset = latin1;

create table SurveyRankingQuestionTemplate
(
    ID             int auto_increment
        primary key,
    MaxItemsToRank int default 0           not null,
    Intro          mediumtext charset utf8 null
)
    charset = latin1;

create table SurveyReport
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('SurveyReport') charset utf8 default 'SurveyReport' null,
    LastEdited datetime                                                  null,
    Created    datetime                                                  null,
    Name       varchar(254) charset utf8                                 null,
    Display    tinyint unsigned                   default '1'            not null,
    TemplateID int                                                       null
)
    charset = latin1;

create index ClassName
    on SurveyReport (ClassName);

create index TemplateID
    on SurveyReport (TemplateID);

create table SurveyReportFilter
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('SurveyReportFilter') charset utf8 default 'SurveyReportFilter' null,
    LastEdited datetime                                                              null,
    Created    datetime                                                              null,
    Name       varchar(255) charset utf8                                             null,
    Label      varchar(255) charset utf8                                             null,
    `Order`    int                                      default 0                    not null,
    QuestionID int                                                                   null,
    ReportID   int                                                                   null
)
    charset = latin1;

create index ClassName
    on SurveyReportFilter (ClassName);

create index QuestionID
    on SurveyReportFilter (QuestionID);

create index ReportID
    on SurveyReportFilter (ReportID);

create table SurveyReportGraph
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('SurveyReportGraph') charset utf8        default 'SurveyReportGraph' null,
    LastEdited datetime                                                                   null,
    Created    datetime                                                                   null,
    Name       varchar(255) charset utf8                                                  null,
    Label      mediumtext charset utf8                                                    null,
    Type       enum ('pie', 'bars', 'multibars') charset utf8 default 'pie'               null,
    `Order`    int                                            default 0                   not null,
    QuestionID int                                                                        null,
    SectionID  int                                                                        null
)
    charset = latin1;

create index ClassName
    on SurveyReportGraph (ClassName);

create index QuestionID
    on SurveyReportGraph (QuestionID);

create index SectionID
    on SurveyReportGraph (SectionID);

create table SurveyReportSection
(
    ID          int auto_increment
        primary key,
    ClassName   enum ('SurveyReportSection') charset utf8 default 'SurveyReportSection' null,
    LastEdited  datetime                                                                null,
    Created     datetime                                                                null,
    Name        varchar(255) charset utf8                                               null,
    `Order`     int                                       default 0                     not null,
    Description mediumtext charset utf8                                                 null,
    ReportID    int                                                                     null
)
    charset = latin1;

create index ClassName
    on SurveyReportSection (ClassName);

create index ReportID
    on SurveyReportSection (ReportID);

create table SurveySingleValueTemplateQuestion
(
    ID           int auto_increment
        primary key,
    InitialValue mediumtext charset utf8 null
)
    charset = latin1;

create table SurveySingleValueTemplateQuestion_ValidationRules
(
    ID                                  int auto_increment
        primary key,
    SurveySingleValueTemplateQuestionID int default 0 not null,
    SurveySingleValueValidationRuleID   int default 0 not null
)
    charset = latin1;

create index SurveySingleValueTemplateQuestionID
    on SurveySingleValueTemplateQuestion_ValidationRules (SurveySingleValueTemplateQuestionID);

create index SurveySingleValueValidationRuleID
    on SurveySingleValueTemplateQuestion_ValidationRules (SurveySingleValueValidationRuleID);

create table SurveySingleValueValidationRule
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('SurveySingleValueValidationRule', 'SurveyCustomValidationRule', 'SurveyMaxLengthValidationRule', 'SurveyMinLengthValidationRule', 'SurveyNumberValidationRule', 'SurveyRangeValidationRule') charset utf8 default 'SurveySingleValueValidationRule' null,
    LastEdited datetime                                                                                                                                                                                                                                                   null,
    Created    datetime                                                                                                                                                                                                                                                   null,
    Name       varchar(255) charset utf8                                                                                                                                                                                                                                  null,
    Message    mediumtext charset utf8                                                                                                                                                                                                                                    null,
    constraint Name
        unique (Name)
)
    charset = latin1;

create index ClassName
    on SurveySingleValueValidationRule (ClassName);

create table SurveyStep
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('SurveyStep', 'SurveyDynamicEntityStep', 'SurveyRegularStep') charset utf8 default 'SurveyStep' null,
    LastEdited datetime                                                                                              null,
    Created    datetime                                                                                              null,
    State      enum ('INCOMPLETE', 'COMPLETE') charset utf8                                     default 'INCOMPLETE' null,
    TemplateID int                                                                                                   null,
    SurveyID   int                                                                                                   null
)
    charset = latin1;

create index ClassName
    on SurveyStep (ClassName);

create index SurveyID
    on SurveyStep (SurveyID);

create index TemplateID
    on SurveyStep (TemplateID);

create table SurveyStepTemplate
(
    ID               int auto_increment
        primary key,
    ClassName        enum ('SurveyStepTemplate', 'SurveyDynamicEntityStepTemplate', 'SurveyRegularStepTemplate', 'SurveyThankYouStepTemplate', 'SurveyReviewStepTemplate') charset utf8 default 'SurveyStepTemplate' null,
    LastEdited       datetime                                                                                                                                                                                        null,
    Created          datetime                                                                                                                                                                                        null,
    Name             varchar(255) charset utf8                                                                                                                                                                       null,
    Content          mediumtext charset utf8                                                                                                                                                                         null,
    FriendlyName     mediumtext charset utf8                                                                                                                                                                         null,
    `Order`          int                                                                                                                                                                default 0                    not null,
    SkipStep         tinyint unsigned                                                                                                                                                   default '0'                  not null,
    SurveyTemplateID int                                                                                                                                                                                             null,
    constraint SurveyTemplateID_Name
        unique (SurveyTemplateID, Name)
)
    charset = latin1;

create index ClassName
    on SurveyStepTemplate (ClassName);

create index SurveyTemplateID
    on SurveyStepTemplate (SurveyTemplateID);

create table SurveyStepTemplate_DependsOn
(
    ID                       int auto_increment
        primary key,
    SurveyStepTemplateID     int                                          default 0         not null,
    SurveyQuestionTemplateID int                                          default 0         not null,
    ValueID                  int                                          default 0         not null,
    Operator                 enum ('Equal', 'Not-Equal') charset utf8     default 'Equal'   null,
    Visibility               enum ('Visible', 'Not-Visible') charset utf8 default 'Visible' null,
    BooleanOperatorOnValues  enum ('And', 'Or') charset utf8              default 'And'     null
)
    charset = latin1;

create index SurveyQuestionTemplateID
    on SurveyStepTemplate_DependsOn (SurveyQuestionTemplateID);

create index SurveyStepTemplateID
    on SurveyStepTemplate_DependsOn (SurveyStepTemplateID);

create table SurveyTemplate
(
    ID          int auto_increment
        primary key,
    ClassName   enum ('SurveyTemplate', 'EntitySurveyTemplate') charset utf8 default 'SurveyTemplate' null,
    LastEdited  datetime                                                                              null,
    Created     datetime                                                                              null,
    Title       varchar(255) charset utf8                                                             null,
    StartDate   datetime                                                                              null,
    EndDate     datetime                                                                              null,
    Enabled     tinyint unsigned                                             default '0'              not null,
    CreatedByID int                                                                                   null
)
    charset = latin1;

create index ClassName
    on SurveyTemplate (ClassName);

create index CreatedByID
    on SurveyTemplate (CreatedByID);

create table SurveyThankYouStepTemplate
(
    ID              int auto_increment
        primary key,
    EmailTemplateID int null
)
    charset = latin1;

create index EmailTemplateID
    on SurveyThankYouStepTemplate (EmailTemplateID);

create table Tag
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('Tag') charset utf8 default 'Tag' null,
    LastEdited datetime                                null,
    Created    datetime                                null,
    Tag        varchar(50) charset utf8                null
)
    charset = latin1;

create index ClassName
    on Tag (ClassName);

create table Team
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('Team') charset utf8 default 'Team' null,
    LastEdited datetime                                  null,
    Created    datetime                                  null,
    Name       mediumtext charset utf8                   null,
    CompanyID  int                                       null
)
    charset = latin1;

create index ClassName
    on Team (ClassName);

create index CompanyID
    on Team (CompanyID);

create table TeamInvitation
(
    ID               int auto_increment
        primary key,
    ClassName        enum ('TeamInvitation') charset utf8 default 'TeamInvitation' null,
    LastEdited       datetime                                                      null,
    Created          datetime                                                      null,
    Email            mediumtext charset utf8                                       null,
    FirstName        mediumtext charset utf8                                       null,
    LastName         mediumtext charset utf8                                       null,
    ConfirmationHash mediumtext charset utf8                                       null,
    IsConfirmed      tinyint unsigned                     default '0'              not null,
    ConfirmationDate datetime                                                      null,
    TeamID           int                                                           null,
    MemberID         int                                                           null
)
    charset = latin1;

create index ClassName
    on TeamInvitation (ClassName);

create index MemberID
    on TeamInvitation (MemberID);

create index TeamID
    on TeamInvitation (TeamID);

create table Team_Members
(
    ID        int auto_increment
        primary key,
    TeamID    int default 0 not null,
    MemberID  int default 0 not null,
    DateAdded datetime      null
)
    charset = latin1;

create index MemberID
    on Team_Members (MemberID);

create index TeamID
    on Team_Members (TeamID);

create table Topic
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('Topic') charset utf8 default 'Topic' null,
    LastEdited datetime                                    null,
    Created    datetime                                    null,
    Name       varchar(255) charset utf8                   null
)
    charset = latin1;

create index ClassName
    on Topic (ClassName);

create table TrackAnswer
(
    ID             int auto_increment
        primary key,
    ClassName      enum ('TrackAnswer') charset utf8 default 'TrackAnswer' null,
    LastEdited     datetime                                                null,
    Created        datetime                                                null,
    Value          mediumtext charset utf8                                 null,
    QuestionID     int                                                     null,
    PresentationID int                                                     null
)
    charset = latin1;

create index ClassName
    on TrackAnswer (ClassName);

create index PresentationID
    on TrackAnswer (PresentationID);

create index QuestionID
    on TrackAnswer (QuestionID);

create table TrackCheckBoxListQuestionTemplate
(
    ID int auto_increment
        primary key
)
    charset = latin1;

create table TrackCheckBoxQuestionTemplate
(
    ID int auto_increment
        primary key
)
    charset = latin1;

create table TrackDropDownQuestionTemplate
(
    ID                int auto_increment
        primary key,
    IsMultiSelect     tinyint unsigned default '0' not null,
    IsCountrySelector tinyint unsigned default '0' not null,
    UseChosenPlugin   tinyint unsigned default '0' not null
)
    charset = latin1;

create table TrackLiteralContentQuestionTemplate
(
    ID      int auto_increment
        primary key,
    Content mediumtext charset utf8 null
)
    charset = latin1;

create table TrackMultiValueQuestionTemplate
(
    ID             int auto_increment
        primary key,
    EmptyString    varchar(255) charset utf8 null,
    DefaultValueID int                       null
)
    charset = latin1;

create index DefaultValueID
    on TrackMultiValueQuestionTemplate (DefaultValueID);

create table TrackQuestionTemplate
(
    ID            int auto_increment
        primary key,
    ClassName     enum ('TrackQuestionTemplate', 'TrackLiteralContentQuestionTemplate', 'TrackMultiValueQuestionTemplate', 'TrackCheckBoxListQuestionTemplate', 'TrackDropDownQuestionTemplate', 'TrackRadioButtonListQuestionTemplate', 'TrackSingleValueTemplateQuestion', 'TrackCheckBoxQuestionTemplate', 'TrackTextBoxQuestionTemplate') charset utf8 default 'TrackQuestionTemplate' null,
    LastEdited    datetime                                                                                                                                                                                                                                                                                                                                                                 null,
    Created       datetime                                                                                                                                                                                                                                                                                                                                                                 null,
    Name          varchar(255) charset utf8                                                                                                                                                                                                                                                                                                                                                null,
    Label         mediumtext charset utf8                                                                                                                                                                                                                                                                                                                                                  null,
    Mandatory     tinyint unsigned                                                                                                                                                                                                                                                                                                                         default '0'                     not null,
    ReadOnly      tinyint unsigned                                                                                                                                                                                                                                                                                                                         default '0'                     not null,
    AfterQuestion enum ('Title', 'CategoryContainer', 'LevelProblemAddressed', 'AttendeesExpectedLearnt', 'Last') charset utf8                                                                                                                                                                                                                             default 'Last'                  null
)
    charset = latin1;

create index ClassName
    on TrackQuestionTemplate (ClassName);

create table TrackQuestionValueTemplate
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('TrackQuestionValueTemplate') charset utf8 default 'TrackQuestionValueTemplate' null,
    LastEdited datetime                                                                              null,
    Created    datetime                                                                              null,
    Value      varchar(255) charset utf8                                                             null,
    `Order`    int                                              default 0                            not null,
    Label      mediumtext charset utf8                                                               null,
    OwnerID    int                                                                                   null
)
    charset = latin1;

create index ClassName
    on TrackQuestionValueTemplate (ClassName);

create index OwnerID
    on TrackQuestionValueTemplate (OwnerID);

create table TrackRadioButtonListQuestionTemplate
(
    ID int auto_increment
        primary key
)
    charset = latin1;

create table TrackSingleValueTemplateQuestion
(
    ID           int auto_increment
        primary key,
    InitialValue mediumtext charset utf8 null
)
    charset = latin1;

create table TrackTagGroup
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('TrackTagGroup') charset utf8 default 'TrackTagGroup' null,
    LastEdited datetime                                                    null,
    Created    datetime                                                    null,
    Name       varchar(50) charset utf8                                    null,
    Label      varchar(50) charset utf8                                    null,
    `Order`    int                                 default 0               not null,
    Mandatory  tinyint unsigned                    default '0'             not null,
    SummitID   int                                                         null
)
    charset = latin1;

create index ClassName
    on TrackTagGroup (ClassName);

create index SummitID
    on TrackTagGroup (SummitID);

create table TrackTagGroup_AllowedTags
(
    ID              int auto_increment
        primary key,
    TrackTagGroupID int              default 0   not null,
    TagID           int              default 0   not null,
    IsDefault       tinyint unsigned default '0' not null
)
    charset = latin1;

create index TagID
    on TrackTagGroup_AllowedTags (TagID);

create index TrackTagGroupID
    on TrackTagGroup_AllowedTags (TrackTagGroupID);

create table TrackTextBoxQuestionTemplate
(
    ID int auto_increment
        primary key
)
    charset = latin1;

create table TrainingActivity
(
    ID          int auto_increment
        primary key,
    ClassName   enum ('TrainingActivity') charset utf8 default 'TrainingActivity' null,
    LastEdited  datetime                                                          null,
    Created     datetime                                                          null,
    Title       mediumtext charset utf8                                           null,
    Link        mediumtext charset utf8                                           null,
    Description mediumtext charset utf8                                           null,
    StartDate   date                                                              null,
    EndDate     date                                                              null
)
    charset = latin1;

create index ClassName
    on TrainingActivity (ClassName);

create table TrainingCourse
(
    ID                int auto_increment
        primary key,
    ClassName         enum ('TrainingCourse') charset utf8 default 'TrainingCourse' null,
    LastEdited        datetime                                                      null,
    Created           datetime                                                      null,
    Name              mediumtext charset utf8                                       null,
    Paid              tinyint unsigned                     default '0'              not null,
    Description       mediumtext charset utf8                                       null,
    Link              mediumtext charset utf8                                       null,
    Online            tinyint unsigned                     default '0'              not null,
    TrainingServiceID int                                                           null,
    TypeID            int                                                           null,
    LevelID           int                                                           null
)
    charset = latin1;

create index ClassName
    on TrainingCourse (ClassName);

create index LevelID
    on TrainingCourse (LevelID);

create index TrainingServiceID
    on TrainingCourse (TrainingServiceID);

create index TypeID
    on TrainingCourse (TypeID);

create table TrainingCourseLevel
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('TrainingCourseLevel') charset utf8 default 'TrainingCourseLevel' null,
    LastEdited datetime                                                                null,
    Created    datetime                                                                null,
    Level      mediumtext charset utf8                                                 null
)
    charset = latin1;

create index ClassName
    on TrainingCourseLevel (ClassName);

create table TrainingCoursePrerequisite
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('TrainingCoursePrerequisite') charset utf8 default 'TrainingCoursePrerequisite' null,
    LastEdited datetime                                                                              null,
    Created    datetime                                                                              null,
    Name       mediumtext charset utf8                                                               null
)
    charset = latin1;

create index ClassName
    on TrainingCoursePrerequisite (ClassName);

create table TrainingCourseSchedule
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('TrainingCourseSchedule') charset utf8 default 'TrainingCourseSchedule' null,
    LastEdited datetime                                                                      null,
    Created    datetime                                                                      null,
    City       mediumtext charset utf8                                                       null,
    State      mediumtext charset utf8                                                       null,
    Country    mediumtext charset utf8                                                       null,
    CourseID   int                                                                           null
)
    charset = latin1;

create index ClassName
    on TrainingCourseSchedule (ClassName);

create index CourseID
    on TrainingCourseSchedule (CourseID);

create table TrainingCourseScheduleTime
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('TrainingCourseScheduleTime') charset utf8 default 'TrainingCourseScheduleTime' null,
    LastEdited datetime                                                                              null,
    Created    datetime                                                                              null,
    StartDate  date                                                                                  null,
    EndDate    date                                                                                  null,
    Link       mediumtext charset utf8                                                               null,
    LocationID int                                                                                   null
)
    charset = latin1;

create index ClassName
    on TrainingCourseScheduleTime (ClassName);

create index LocationID
    on TrainingCourseScheduleTime (LocationID);

create table TrainingCourseType
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('TrainingCourseType') charset utf8 default 'TrainingCourseType' null,
    LastEdited datetime                                                              null,
    Created    datetime                                                              null,
    Type       mediumtext charset utf8                                               null
)
    charset = latin1;

create index ClassName
    on TrainingCourseType (ClassName);

create table TrainingCourse_Prerequisites
(
    ID                           int auto_increment
        primary key,
    TrainingCourseID             int default 0 not null,
    TrainingCoursePrerequisiteID int default 0 not null
)
    charset = latin1;

create index TrainingCourseID
    on TrainingCourse_Prerequisites (TrainingCourseID);

create index TrainingCoursePrerequisiteID
    on TrainingCourse_Prerequisites (TrainingCoursePrerequisiteID);

create table TrainingCourse_Projects
(
    ID               int auto_increment
        primary key,
    TrainingCourseID int default 0 not null,
    ProjectID        int default 0 not null
)
    charset = latin1;

create index ProjectID
    on TrainingCourse_Projects (ProjectID);

create index TrainingCourseID
    on TrainingCourse_Projects (TrainingCourseID);

create table TrainingService
(
    ID       int auto_increment
        primary key,
    Priority varchar(5) charset utf8 null
)
    charset = latin1;

create table UserStoriesIndustry
(
    ID           int auto_increment
        primary key,
    ClassName    enum ('UserStoriesIndustry') charset utf8 default 'UserStoriesIndustry' null,
    LastEdited   datetime                                                                null,
    Created      datetime                                                                null,
    IndustryName mediumtext charset utf8                                                 null,
    Active       tinyint unsigned                          default '0'                   not null
)
    charset = latin1;

create index ClassName
    on UserStoriesIndustry (ClassName);

create table UserStoriesPage
(
    ID          int auto_increment
        primary key,
    HeaderText  mediumtext charset utf8   null,
    HeroText    mediumtext charset utf8   null,
    YouTubeID   varchar(255) charset utf8 null,
    HeroImageID int                       null
)
    charset = latin1;

create index HeroImageID
    on UserStoriesPage (HeroImageID);

create table UserStoriesPage_Live
(
    ID          int auto_increment
        primary key,
    HeaderText  mediumtext charset utf8   null,
    HeroText    mediumtext charset utf8   null,
    YouTubeID   varchar(255) charset utf8 null,
    HeroImageID int                       null
)
    charset = latin1;

create index HeroImageID
    on UserStoriesPage_Live (HeroImageID);

create table UserStoriesPage_versions
(
    ID          int auto_increment
        primary key,
    RecordID    int default 0             not null,
    Version     int default 0             not null,
    HeaderText  mediumtext charset utf8   null,
    HeroText    mediumtext charset utf8   null,
    YouTubeID   varchar(255) charset utf8 null,
    HeroImageID int                       null,
    constraint RecordID_Version
        unique (RecordID, Version)
)
    charset = latin1;

create index HeroImageID
    on UserStoriesPage_versions (HeroImageID);

create index RecordID
    on UserStoriesPage_versions (RecordID);

create index Version
    on UserStoriesPage_versions (Version);

create table UserStoryDO
(
    ID               int auto_increment
        primary key,
    ClassName        enum ('UserStoryDO') charset utf8 default 'UserStoryDO' null,
    LastEdited       datetime                                                null,
    Created          datetime                                                null,
    Name             mediumtext charset utf8                                 null,
    Description      mediumtext charset utf8                                 null,
    ShortDescription mediumtext charset utf8                                 null,
    Link             mediumtext charset utf8                                 null,
    Active           tinyint unsigned                  default '1'           not null,
    IndustryID       int                                                     null,
    OrganizationID   int                                                     null,
    LocationID       int                                                     null,
    ImageID          int                                                     null
)
    charset = latin1;

create index ClassName
    on UserStoryDO (ClassName);

create index ImageID
    on UserStoryDO (ImageID);

create index IndustryID
    on UserStoryDO (IndustryID);

create index LocationID
    on UserStoryDO (LocationID);

create index OrganizationID
    on UserStoryDO (OrganizationID);

create table UserStoryDO_Tags
(
    ID            int auto_increment
        primary key,
    UserStoryDOID int default 0 not null,
    TagID         int default 0 not null
)
    charset = latin1;

create index TagID
    on UserStoryDO_Tags (TagID);

create index UserStoryDOID
    on UserStoryDO_Tags (UserStoryDOID);

create table UserSurveyPage
(
    ID                     int auto_increment
        primary key,
    LoginPageTitle         mediumtext charset utf8 null,
    LoginPageContent       mediumtext charset utf8 null,
    LoginPageSlide1Content mediumtext charset utf8 null,
    LoginPageSlide2Content mediumtext charset utf8 null,
    LoginPageSlide3Content mediumtext charset utf8 null
)
    charset = latin1;

create table UserSurveyPage_Live
(
    ID                     int auto_increment
        primary key,
    LoginPageTitle         mediumtext charset utf8 null,
    LoginPageContent       mediumtext charset utf8 null,
    LoginPageSlide1Content mediumtext charset utf8 null,
    LoginPageSlide2Content mediumtext charset utf8 null,
    LoginPageSlide3Content mediumtext charset utf8 null
)
    charset = latin1;

create table UserSurveyPage_versions
(
    ID                     int auto_increment
        primary key,
    RecordID               int default 0           not null,
    Version                int default 0           not null,
    LoginPageTitle         mediumtext charset utf8 null,
    LoginPageContent       mediumtext charset utf8 null,
    LoginPageSlide1Content mediumtext charset utf8 null,
    LoginPageSlide2Content mediumtext charset utf8 null,
    LoginPageSlide3Content mediumtext charset utf8 null,
    constraint RecordID_Version
        unique (RecordID, Version)
)
    charset = latin1;

create index RecordID
    on UserSurveyPage_versions (RecordID);

create index Version
    on UserSurveyPage_versions (Version);

create table VideoLink
(
    ID          int auto_increment
        primary key,
    ClassName   enum ('VideoLink', 'MarketingVideo', 'OpenStackDaysVideo') charset utf8 default 'VideoLink' null,
    LastEdited  datetime                                                                                    null,
    Created     datetime                                                                                    null,
    YoutubeID   mediumtext charset utf8                                                                     null,
    Caption     mediumtext charset utf8                                                                     null,
    SortOrder   int                                                                     default 0           not null,
    ThumbnailID int                                                                                         null
)
    charset = latin1;

create index ClassName
    on VideoLink (ClassName);

create index SortOrder
    on VideoLink (SortOrder);

create index ThumbnailID
    on VideoLink (ThumbnailID);

create table VideoPresentation
(
    ID                         int auto_increment
        primary key,
    ClassName                  enum ('VideoPresentation') charset utf8 default 'VideoPresentation' null,
    LastEdited                 datetime                                                            null,
    Created                    datetime                                                            null,
    Name                       mediumtext charset utf8                                             null,
    DisplayOnSite              tinyint unsigned                        default '0'                 not null,
    Featured                   tinyint unsigned                        default '0'                 not null,
    City                       varchar(255) charset utf8                                           null,
    Country                    varchar(255) charset utf8                                           null,
    Description                mediumtext charset utf8                                             null,
    YouTubeID                  varchar(255) charset utf8                                           null,
    URLSegment                 mediumtext charset utf8                                             null,
    StartTime                  varchar(255) charset utf8                                           null,
    EndTime                    varchar(255) charset utf8                                           null,
    Location                   mediumtext charset utf8                                             null,
    Type                       mediumtext charset utf8                                             null,
    Day                        int                                     default 0                   not null,
    Speakers                   mediumtext charset utf8                                             null,
    SlidesLink                 varchar(255) charset utf8                                           null,
    event_key                  varchar(255) charset utf8                                           null,
    IsKeynote                  tinyint unsigned                        default '0'                 not null,
    SchedID                    varchar(50) charset utf8                                            null,
    HostedMediaURL             mediumtext charset utf8                                             null,
    MediaType                  enum ('URL', 'File') charset utf8       default 'URL'               null,
    PresentationCategoryPageID int                                                                 null,
    SummitID                   int                                                                 null,
    MemberID                   int                                                                 null,
    UploadedMediaID            int                                                                 null
)
    charset = latin1;

create index ClassName
    on VideoPresentation (ClassName);

create index MemberID
    on VideoPresentation (MemberID);

create index PresentationCategoryPageID
    on VideoPresentation (PresentationCategoryPageID);

create index SummitID
    on VideoPresentation (SummitID);

create index UploadedMediaID
    on VideoPresentation (UploadedMediaID);

create table VirtualPage
(
    ID                int auto_increment
        primary key,
    VersionID         int default 0 not null,
    CopyContentFromID int           null
)
    charset = latin1;

create index CopyContentFromID
    on VirtualPage (CopyContentFromID);

create table VirtualPage_Live
(
    ID                int auto_increment
        primary key,
    VersionID         int default 0 not null,
    CopyContentFromID int           null
)
    charset = latin1;

create index CopyContentFromID
    on VirtualPage_Live (CopyContentFromID);

create table VirtualPage_versions
(
    ID                int auto_increment
        primary key,
    RecordID          int default 0 not null,
    Version           int default 0 not null,
    VersionID         int default 0 not null,
    CopyContentFromID int           null,
    constraint RecordID_Version
        unique (RecordID, Version)
)
    charset = latin1;

create index CopyContentFromID
    on VirtualPage_versions (CopyContentFromID);

create index RecordID
    on VirtualPage_versions (RecordID);

create index Version
    on VirtualPage_versions (Version);

create table Voter
(
    ID         int auto_increment
        primary key,
    ClassName  enum ('Voter') charset utf8 default 'Voter' null,
    LastEdited datetime                                    null,
    Created    datetime                                    null,
    MemberID   int                                         null
)
    charset = latin1;

create index ClassName
    on Voter (ClassName);

create index MemberID
    on Voter (MemberID);

create table ZenDeskLink
(
    ID                        int auto_increment
        primary key,
    ClassName                 enum ('ZenDeskLink') charset utf8 default 'ZenDeskLink' null,
    LastEdited                datetime                                                null,
    Created                   datetime                                                null,
    Link                      varchar(255) charset utf8                               null,
    OpenStackImplementationID int                                                     null
)
    charset = latin1;

create index ClassName
    on ZenDeskLink (ClassName);

create index OpenStackImplementationID
    on ZenDeskLink (OpenStackImplementationID);

INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20190422151949', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20190506153014', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20190506153909', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20190529015655', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20190529142913', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20190529142927', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20190530205326', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20190530205344', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20190625030955', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20190626125814', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20190629222739', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20190723210551', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20190728200547', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20190824125218', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20190730022151', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20190730031422', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20190801211505', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20190911132806', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20190918111958', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20191016014630', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20191202223721', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20191212002736', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20191220223248', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20191220223253', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20191224021722', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20191224022307', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20191229173636', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20200109171923', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20200110184019', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20191116183316', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20191125210134', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20191206163423', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20200123133515', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20200212023535', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20200212125943', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20200213131907', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20200128184149', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20200128191140', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20200403191418', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20200512132942', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20200512174027', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20200523235306', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20200526174904', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20200601211446', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20200602212951', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20200609105105', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20200616144713', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20200618192655', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20200623191130', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20200623191331', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20200623191754', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20200624132001', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20200629142643', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20200629143447', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20200730135823', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20200803171455', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20200713164340', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20200713164344', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20200817180752', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20200818120409', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20200824140528', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20200831193516', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20200901160152', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20200904155247', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20200910184756', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20200924123949', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20200924203451', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20200924210244', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20200928132323', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20201001182314', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20201008203936', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20201014155708', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20201014155719', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20201014161727', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20201015153512', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20201015153514', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20201015153516', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20201016145706', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20201018045210', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20201021125624', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20201021172434', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20201022181641', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20201027024056', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20201029175540', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20201116151153', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20201119155826', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20201120143925', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20201208150500', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20201208151735', null);
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20210203161916', '2021-04-16 01:48:36');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20210212151954', '2021-04-16 01:48:36');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20210212151956', '2021-04-16 01:48:37');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20210322170708', '2021-04-16 01:48:37');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20210326171114', '2021-04-16 01:48:49');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20210326171117', '2021-04-16 01:48:49');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20210405144636', '2021-04-16 01:48:58');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20210406124904', '2021-04-16 01:48:58');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20210406125358', '2021-04-16 01:49:05');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20210416191958', '2021-04-24 01:55:34');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20210419181056', '2021-04-24 01:55:42');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20210422150202', '2021-04-24 01:55:50');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20210426223306', '2021-04-28 02:10:11');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20210429160901', '2021-04-29 21:21:22');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20210521135639', '2021-06-03 17:09:34');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20210521135642', '2021-06-03 17:09:34');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20210521170713', '2021-06-03 17:09:38');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20210528150223', '2021-06-03 17:09:47');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20210602181838', '2021-06-03 17:09:48');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20210603182544', '2021-06-03 18:50:19');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20210601152355', '2021-06-09 19:32:06');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20210628184207', '2021-06-29 19:26:29');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20210707172103', '2021-07-15 12:13:12');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20210707172106', '2021-07-15 12:13:13');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20210716165815', '2021-07-19 13:01:11');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20210816174116', '2021-08-16 17:48:08');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20210826171650', '2021-08-30 22:47:53');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20210903180455', '2021-09-15 22:09:01');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20210903182620', '2021-09-15 22:09:01');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20210913203442', '2021-09-15 22:09:02');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20210913215613', '2021-09-15 22:09:02');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20210913215614', '2021-09-15 22:09:03');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20211006122424', '2021-10-14 00:30:11');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20211006122426', '2021-10-14 00:30:11');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20211007161147', '2021-10-14 00:30:19');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20211013164919', '2021-10-14 00:30:27');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20211014140751', '2021-10-18 17:53:16');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20211018134022', '2021-10-18 17:58:06');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20211103124532', '2021-11-03 13:11:43');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20211007133152', '2021-11-16 00:01:52');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20211012162726', '2021-11-16 00:02:00');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20211112190853', '2021-11-16 00:02:01');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20211129183414', '2021-11-29 23:31:24');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20211213135926', '2021-12-15 10:49:33');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20220106085440', '2022-03-01 15:52:08');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20220111214358', '2022-03-01 15:52:17');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20220125200224', '2022-03-01 15:52:26');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20220127210145', '2022-03-01 15:52:35');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20220127210146', '2022-03-01 15:52:44');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20220128194504', '2022-03-01 15:52:53');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20220128200351', '2022-03-01 15:53:02');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20220131195047', '2022-03-01 15:53:11');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20220131201421', '2022-03-01 15:53:11');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20220204152158', '2022-03-01 15:53:20');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20220207183947', '2022-03-01 15:53:30');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20220207183951', '2022-03-01 15:53:30');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20220207195239', '2022-03-01 15:53:30');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20220207195617', '2022-03-01 15:55:30');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20220210181934', '2022-03-01 15:55:40');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20220210181935', '2022-03-01 15:55:40');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20220214140659', '2022-03-01 15:55:49');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20220215210214', '2022-03-01 15:55:49');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20220216140653', '2022-03-01 15:55:49');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20220216144229', '2022-03-01 15:55:49');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20220216213443', '2022-03-01 15:55:57');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20220218124421', '2022-03-01 15:55:58');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20220223221730', '2022-03-01 15:55:58');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20220314152133', '2022-03-30 02:31:47');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20220322141015', '2022-03-30 02:31:56');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20220322195257', '2022-03-30 02:32:05');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20220328214032', '2022-03-30 02:32:13');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20220328170502', '2022-04-19 02:08:41');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20220330180247', '2022-04-19 02:08:50');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20220331173736', '2022-04-19 02:08:59');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20220404193539', '2022-04-19 02:09:08');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20220405205916', '2022-04-19 02:09:08');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20220405205925', '2022-04-19 02:09:17');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20220406133959', '2022-04-19 02:09:27');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20220406141529', '2022-04-19 02:09:27');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20220412182357', '2022-04-19 02:09:27');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20220418172350', '2022-04-29 16:36:41');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20220418192910', '2022-04-29 16:36:45');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20220420155435', '2022-04-29 16:36:50');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20220420171938', '2022-04-29 16:36:50');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20220420171940', '2022-04-29 16:36:50');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20220420184724', '2022-04-29 16:36:51');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20220427192118', '2022-04-29 16:36:55');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20220421184853', '2022-05-02 21:19:14');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20220421184854', '2022-05-02 21:19:24');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20220421184855', '2022-05-02 21:19:25');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20220427203735', '2022-05-02 21:19:34');
INSERT INTO DoctrineMigration (version, executed_at) VALUES ('20220503185119', '2022-05-04 03:30:41');
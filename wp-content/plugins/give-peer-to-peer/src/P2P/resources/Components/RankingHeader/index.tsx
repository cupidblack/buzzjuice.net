import {memo} from 'react';
import SearchIcon from '@p2p/Components/SearchIcon';
import styles from './style.module.scss';

interface RankingHeaderProps {
    title: string;
    searchLabel: string;
    searchPlaceholder: string;
    showSearch: boolean;
    onSearchChange: (e) => void;
    isCampaignPage?: boolean;
    viewAllLink?: string;
    viewAllText?: string;
}

/**
 *
 *
 * @since 1.6.0
 */
const RankingHeader = memo(
    ({
        title,
        searchLabel,
        searchPlaceholder,
        onSearchChange,
        showSearch,
        isCampaignPage,
        viewAllLink,
        viewAllText,
    }: RankingHeaderProps) => {
        return (
            <div className={styles.rankingHeader}>
                <header>
                    <h2>{title}</h2>
                    {isCampaignPage && <a href={viewAllLink}>{viewAllText}</a>}
                    {!!showSearch && (
                        <label>
                            <span hidden>{searchLabel}</span>
                            <div>
                                <SearchIcon />
                                <input type="text" placeholder={searchPlaceholder} onChange={onSearchChange} />
                            </div>
                        </label>
                    )}
                </header>
            </div>
        );
    }
);

export default RankingHeader;
